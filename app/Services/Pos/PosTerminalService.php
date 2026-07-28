<?php

namespace App\Services\Pos;

use App\Enums\Commerce\ProductStatus;
use App\Enums\Commerce\ProductVisibility;
use App\Enums\Commerce\SaleChannel;
use App\Enums\Erp\PaymentMethod;
use App\Enums\Erp\SaleItemSourceType;
use App\Enums\Erp\SaleSourceType;
use App\Enums\Erp\SaleStatus;
use App\Models\Tenant\Category;
use App\Models\Tenant\Customer;
use App\Models\Tenant\PosPaymentMethod;
use App\Models\Tenant\PosRegister;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\Sale;
use App\Services\Commerce\CatalogService;
use App\Services\Commerce\UnifiedSalesEngine;
use App\Exceptions\Pos\PosRegisterGuardException;
use App\Support\Erp\Decimal;
use App\Support\Erp\TenantMediaUrl;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * POS interface application service — thin orchestration over existing POS/commerce services.
 * No store checkout coupling.
 */
final class PosTerminalService
{
    public function __construct(
        private readonly UnifiedSalesEngine $sales,
        private readonly CashierSessionService $sessions,
        private readonly CashMovementService $movements,
        private readonly PosRegisterGuard $guard,
        private readonly ShiftReportService $reports,
        private readonly CatalogService $catalog,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function bootstrap(?int $registerId = null): array
    {
        $user = Auth::guard('tenant')->user();
        $registers = PosRegister::query()
            ->with(['branch', 'cashDrawer', 'warehouse'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $register = null;
        if ($registerId) {
            $register = $registers->firstWhere('id', $registerId);
        }
        if (! $register) {
            $open = $registers->first(fn (PosRegister $r) => $r->openSession()?->user_id === $user?->id);
            $register = $open ?: $registers->first();
        }

        $session = $register?->sessions()
            ->whereIn('status', ['opening', 'opened', 'closing'])
            ->where('user_id', $user?->id)
            ->latest('opened_at')
            ->first();

        $settings = $this->sessions->settings();
        $paymentMethods = PosPaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (PosPaymentMethod $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'code' => $m->code,
                'type' => $m->type,
                'opens_cash_drawer' => $m->opens_cash_drawer,
            ])
            ->values()
            ->all();

        if ($paymentMethods === []) {
            $paymentMethods = [
                ['id' => null, 'name' => __('commerce.pos_payment_types.cash'), 'code' => 'cash', 'type' => 'cash', 'opens_cash_drawer' => true],
                ['id' => null, 'name' => __('commerce.pos_payment_types.card'), 'code' => 'card', 'type' => 'card', 'opens_cash_drawer' => false],
                ['id' => null, 'name' => __('commerce.pos_payment_types.transfer'), 'code' => 'transfer', 'type' => 'transfer', 'opens_cash_drawer' => false],
                ['id' => null, 'name' => __('commerce.pos_payment_types.other'), 'code' => 'other', 'type' => 'other', 'opens_cash_drawer' => false],
            ];
        }

        return [
            'user' => [
                'id' => $user?->id,
                'name' => $user?->name,
            ],
            'locale' => app()->getLocale(),
            'registers' => $registers->map(fn (PosRegister $r) => $this->registerPayload($r))->values()->all(),
            'register' => $register ? $this->registerPayload($register) : null,
            'session' => $session ? $this->sessionPayload($session) : null,
            'payment_methods' => $paymentMethods,
            'settings' => [
                'require_open_session' => $settings->require_open_session,
                'allow_suspend_sales' => $settings->allow_suspend_sales,
                'suspend_expires_minutes' => $settings->suspend_expires_minutes,
                'default_currency' => $settings->default_currency ?: 'EGP',
                'receipt_number_strategy' => $settings->receipt_number_strategy?->value,
            ],
            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('order')
                ->orderBy('name')
                ->get(['id', 'name', 'parent_id'])
                ->map(fn (Category $c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'parent_id' => $c->parent_id,
                ])
                ->values()
                ->all(),
            'shortcuts' => [
                'search' => 'F2',
                'customer' => 'F4',
                'pay' => 'F8',
                'suspend' => 'F9',
                'close_modal' => 'Escape',
            ],
        ];
    }

    public function products(?string $search = null, ?int $categoryId = null, ?string $barcode = null, int $perPage = 24): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['media', 'variants', 'categories:id,name'])
            ->where('status', ProductStatus::Active->value)
            ->whereIn('visibility', [
                ProductVisibility::Visible->value,
                ProductVisibility::PosOnly->value,
            ]);

        if ($categoryId) {
            $query->whereHas('categories', fn ($q) => $q->where('categories.id', $categoryId));
        }

        if ($barcode) {
            $query->where(function ($q) use ($barcode) {
                $q->where('barcode', $barcode)
                    ->orWhere('sku', $barcode)
                    ->orWhereHas('variants', fn ($vq) => $vq->where('barcode', $barcode)->orWhere('sku', $barcode));
            });
        } elseif ($search) {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhere('barcode', 'like', $like)
                    ->orWhereHas('variants', fn ($vq) => $vq->where('sku', 'like', $like)->orWhere('barcode', 'like', $like));
            });
        }

        return $query->orderBy('name')->paginate($perPage)
            ->through(fn (Product $product) => $this->productPayload($product));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function lookupBarcode(string $code): array
    {
        $hit = $this->catalog->findBySkuOrBarcode($code);
        if (! $hit) {
            return [];
        }

        if ($hit instanceof ProductVariant) {
            $product = $hit->product()->with(['media', 'variants', 'categories:id,name'])->first();
            if (! $product) {
                return [];
            }

            return [[
                ...$this->productPayload($product),
                'matched_variant_id' => $hit->id,
            ]];
        }

        $product = Product::query()->with(['media', 'variants', 'categories:id,name'])->find($hit->id);

        return $product ? [$this->productPayload($product)] : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function searchCustomers(string $q, int $limit = 20): array
    {
        $term = '%'.$q.'%';

        return Customer::query()
            ->with('contacts')
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', $term)
                    ->orWhereHas('contacts', fn ($c) => $c->where('value', 'like', $term));
            })
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Customer $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->primaryPhone(),
                'email' => $c->primaryEmail(),
            ])
            ->values()
            ->all();
    }

    public function quickCreateCustomer(string $name, ?string $phone = null): array
    {
        $customer = Customer::query()->create(['name' => $name]);
        if ($phone) {
            $customer->contacts()->create([
                'type' => 'phone',
                'value' => $phone,
                'is_primary' => true,
            ]);
        }

        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $phone,
            'email' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function checkout(array $payload): array
    {
        $register = PosRegister::query()->findOrFail($payload['register_id']);
        $guarded = $this->assertCanOperate($register);

        $items = $this->buildSaleItems($payload['items'] ?? []);
        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => __('commerce.validation.pos_cart_empty'),
            ]);
        }

        $payments = $payload['payments'] ?? [];
        if ($payments === []) {
            throw ValidationException::withMessages([
                'payments' => __('commerce.validation.pos_payment_required'),
            ]);
        }

        return DB::connection('tenant')->transaction(function () use ($payload, $guarded, $items, $payments) {
            $sale = $this->sales->createDraftSale(SaleChannel::Pos, [
                'pos_register_id' => $guarded['register']->id,
                'cashier_session_id' => $guarded['session']->id,
                'branch_id' => $guarded['register']->branch_id,
                'customer_id' => $payload['customer_id'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'discount_total' => Decimal::money($payload['discount_total'] ?? '0'),
                'currency_code' => $payload['currency_code'] ?? 'EGP',
            ], $items);

            $completed = $this->sales->completeSale($sale, true);
            $invoice = $completed['invoice'];
            $confirmed = $completed['sale'];

            $paidTotal = '0.00';
            foreach ($payments as $payment) {
                $paidTotal = Decimal::money(Decimal::add($paidTotal, Decimal::money($payment['amount'] ?? '0')));
            }

            $grand = Decimal::money($confirmed->grand_total);
            if (Decimal::cmp($paidTotal, $grand, 2) < 0) {
                throw ValidationException::withMessages([
                    'payments' => __('commerce.validation.pos_payment_mismatch'),
                ]);
            }

            $remainingDue = $grand;
            $paymentRows = [];
            foreach ($payments as $index => $payment) {
                $amount = Decimal::money($payment['amount'] ?? '0');
                $toPost = Decimal::cmp($amount, $remainingDue, 2) > 0 ? $remainingDue : $amount;
                if (! Decimal::isPositive($toPost, 2)) {
                    continue;
                }

                $method = $this->mapPaymentMethod((string) ($payment['type'] ?? 'cash'));
                $row = $this->sales->recordPayment(
                    $invoice->fresh(),
                    $toPost,
                    $method,
                    $payment['reference'] ?? null,
                    null,
                    $payment['notes'] ?? null,
                    ($payload['idempotency_key'] ?? null) ? ($payload['idempotency_key'].':pay:'.$index) : null,
                    (string) ($payment['type'] ?? 'cash'),
                    $payment['code'] ?? null,
                );
                $paymentRows[] = [
                    'id' => $row->id,
                    'amount' => (string) $row->amount,
                    'method' => $method->value,
                    'type' => $payment['type'] ?? 'cash',
                    'tendered' => $amount,
                ];
                $remainingDue = Decimal::money(Decimal::sub($remainingDue, $toPost));
            }

            $change = Decimal::cmp($paidTotal, $grand, 2) > 0
                ? Decimal::money(Decimal::sub($paidTotal, $grand))
                : '0.00';

            return [
                'sale' => $this->salePayload($confirmed->fresh(['items', 'customer'])),
                'invoice' => [
                    'id' => $invoice->id,
                    'document_number' => $invoice->document_number,
                    'grand_total' => (string) $invoice->grand_total,
                    'paid_amount' => (string) $invoice->fresh()->paid_amount,
                    'due_amount' => (string) $invoice->fresh()->due_amount,
                ],
                'payments' => $paymentRows,
                'paid_total' => $paidTotal,
                'change' => $change,
                'receipt_url' => route('filament.tenant.pos.receipt', ['sale' => $confirmed->id]),
            ];
        });
    }

    /**
     * @param  list<array<string, mixed>>  $rawItems
     * @return list<array<string, mixed>>
     */
    public function buildSaleItems(array $rawItems): array
    {
        $lines = [];
        foreach ($rawItems as $row) {
            $product = Product::query()->with('variants')->find($row['product_id'] ?? null);
            if (! $product || $product->status !== ProductStatus::Active) {
                throw ValidationException::withMessages([
                    'items' => __('commerce.validation.pos_product_unavailable'),
                ]);
            }

            $variant = null;
            if (! empty($row['product_variant_id'])) {
                $variant = $product->variants->firstWhere('id', (int) $row['product_variant_id']);
                if (! $variant || ! $variant->is_active) {
                    throw ValidationException::withMessages([
                        'items' => __('commerce.validation.pos_variant_unavailable'),
                    ]);
                }
            } elseif ($product->variants->where('is_active', true)->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'items' => __('commerce.validation.pos_variant_required'),
                ]);
            }

            $qty = Decimal::of($row['quantity'] ?? '0');
            if (! Decimal::isPositive($qty)) {
                throw ValidationException::withMessages([
                    'items' => __('commerce.validation.pos_invalid_quantity'),
                ]);
            }

            $unitPrice = $variant
                ? Decimal::money($variant->sale_price ?? $variant->price ?? '0')
                : Decimal::money($product->sale_price ?? $product->price ?? '0');

            $discount = Decimal::money($row['discount'] ?? '0');
            if (Decimal::isNegative($discount) || Decimal::cmp($discount, Decimal::mul($qty, $unitPrice), 2) > 0) {
                throw ValidationException::withMessages([
                    'items' => __('commerce.validation.pos_invalid_discount'),
                ]);
            }

            $stockQty = $variant ? (int) $variant->quantity : (int) $product->quantity;
            if ($product->track_stock && $product->disable_orders_for_no_stock && Decimal::cmp($qty, (string) $stockQty) > 0) {
                throw ValidationException::withMessages([
                    'items' => __('commerce.validation.pos_insufficient_stock', ['product' => $product->name]),
                ]);
            }

            $lines[] = [
                'source_type' => SaleItemSourceType::Commerce->value,
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'description_snapshot' => $product->name.($variant ? ' / '.$variant->sku : ''),
                'sku_snapshot' => $variant?->sku ?: $product->sku,
                'unit_id' => $product->unit_id,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'discount' => $discount,
                'tax' => Decimal::money($row['tax'] ?? '0'),
                'notes' => $row['notes'] ?? null,
            ];
        }

        return $lines;
    }

    private function mapPaymentMethod(string $type): PaymentMethod
    {
        return match ($type) {
            'cash' => PaymentMethod::Cash,
            'card' => PaymentMethod::Card,
            'transfer' => PaymentMethod::BankTransfer,
            'wallet' => PaymentMethod::Wallet,
            default => PaymentMethod::Other,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function productPayload(Product $product): array
    {
        $image = $product->media->first()?->file;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'price' => (string) ($product->sale_price ?? $product->price ?? '0'),
            'base_price' => (string) ($product->price ?? '0'),
            'quantity' => (int) $product->quantity,
            'track_stock' => (bool) $product->track_stock,
            'image_url' => TenantMediaUrl::make($image),
            'categories' => $product->categories->pluck('id')->all(),
            'variants' => $product->variants->where('is_active', true)->values()->map(fn (ProductVariant $v) => [
                'id' => $v->id,
                'sku' => $v->sku,
                'barcode' => $v->barcode,
                'price' => (string) ($v->sale_price ?? $v->price ?? '0'),
                'quantity' => (int) $v->quantity,
                'image_url' => TenantMediaUrl::make($v->image),
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function sessionPayload($session): array
    {
        return [
            'id' => $session->id,
            'status' => $session->status->value ?? $session->status,
            'opened_at' => optional($session->opened_at)?->toIso8601String(),
            'opening_balance' => (string) $session->opening_balance,
            'opening_notes' => $session->opening_notes,
            'pos_register_id' => $session->pos_register_id,
            'branch_id' => $session->branch_id,
            'device_name' => $session->device_name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function registerPayload(PosRegister $register): array
    {
        return [
            'id' => $register->id,
            'name' => $register->name,
            'code' => $register->code,
            'branch' => [
                'id' => $register->branch_id,
                'name' => $register->branch?->name,
                'code' => $register->branch?->code,
            ],
            'warehouse_id' => $register->warehouse_id,
            'cash_drawer' => $register->cashDrawer ? [
                'id' => $register->cashDrawer->id,
                'name' => $register->cashDrawer->name,
            ] : null,
            'has_open_session' => (bool) $register->openSession(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function salePayload(Sale $sale): array
    {
        return [
            'id' => $sale->id,
            'document_number' => $sale->document_number,
            'receipt_number' => $sale->receipt_number,
            'status' => $sale->status->value ?? $sale->status,
            'is_suspended' => (bool) $sale->is_suspended,
            'suspended_at' => optional($sale->suspended_at)?->toIso8601String(),
            'suspended_until' => optional($sale->suspended_until)?->toIso8601String(),
            'customer_id' => $sale->customer_id,
            'customer_name' => $sale->customer?->name,
            'subtotal' => (string) $sale->subtotal,
            'discount_total' => (string) $sale->discount_total,
            'tax_total' => (string) $sale->tax_total,
            'grand_total' => (string) $sale->grand_total,
            'notes' => $sale->notes,
            'items' => $sale->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'description' => $item->description_snapshot,
                'sku' => $item->sku_snapshot,
                'quantity' => (string) $item->quantity,
                'unit_price' => (string) $item->unit_price,
                'discount' => (string) $item->discount,
                'tax' => (string) $item->tax,
                'line_total' => (string) $item->line_total,
                'notes' => $item->notes,
            ])->values()->all(),
        ];
    }

    public function sessions(): CashierSessionService
    {
        return $this->sessions;
    }

    public function movements(): CashMovementService
    {
        return $this->movements;
    }

    public function salesEngine(): UnifiedSalesEngine
    {
        return $this->sales;
    }

    public function reports(): ShiftReportService
    {
        return $this->reports;
    }

    public function guard(): PosRegisterGuard
    {
        return $this->guard;
    }

    /**
     * @return array{register: PosRegister, session: \App\Models\Tenant\CashierSession, user: \App\Models\TenantUser}
     */
    public function assertCanOperate(PosRegister $register): array
    {
        try {
            return $this->guard->assertCanOperate($register);
        } catch (PosRegisterGuardException $e) {
            throw ValidationException::withMessages([
                'session' => $e->getMessage(),
            ]);
        }
    }

    public function abandonPosSale(Sale $sale, ?string $reason = null): Sale
    {
        if ($sale->is_suspended) {
            return $this->sales->cancelSuspended($sale, $reason);
        }

        if ($sale->status === SaleStatus::Draft && $sale->source_type === SaleSourceType::Pos) {
            $sale->status = SaleStatus::Cancelled;
            $sale->suspend_cancelled_at = now();
            $sale->suspend_cancelled_by = Auth::guard('tenant')->id();
            if ($reason) {
                $sale->notes = trim(($sale->notes ? $sale->notes."\n" : '').$reason);
            }
            $sale->save();

            return $sale->fresh();
        }

        throw ValidationException::withMessages([
            'sale' => __('commerce.validation.sale_not_suspended'),
        ]);
    }
}
