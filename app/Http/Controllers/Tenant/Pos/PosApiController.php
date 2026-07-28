<?php

namespace App\Http\Controllers\Tenant\Pos;

use App\Enums\Commerce\SaleChannel;
use App\Enums\Pos\CashMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Pos\PosCashMovementRequest;
use App\Http\Requests\Tenant\Pos\PosCheckoutRequest;
use App\Http\Requests\Tenant\Pos\PosCloseSessionRequest;
use App\Http\Requests\Tenant\Pos\PosOpenSessionRequest;
use App\Http\Requests\Tenant\Pos\PosQuickCustomerRequest;
use App\Http\Requests\Tenant\Pos\PosSuspendRequest;
use App\Models\Tenant\PosRegister;
use App\Models\Tenant\Sale;
use App\Services\Pos\PosTerminalService;
use App\Support\Erp\Decimal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class PosApiController extends Controller
{
    public function __construct(private readonly PosTerminalService $terminal) {}

    public function bootstrap(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->terminal->bootstrap($request->integer('register_id') ?: null),
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $page = $this->terminal->products(
            search: $request->string('search')->toString() ?: null,
            categoryId: $request->integer('category_id') ?: null,
            barcode: $request->string('barcode')->toString() ?: null,
            perPage: min(50, max(1, $request->integer('per_page') ?: 24)),
        );

        return response()->json($page);
    }

    public function barcode(Request $request): JsonResponse
    {
        $code = trim((string) $request->query('code', ''));
        if ($code === '') {
            throw ValidationException::withMessages([
                'code' => __('commerce.validation.pos_barcode_required'),
            ]);
        }

        return response()->json([
            'data' => $this->terminal->lookupBarcode($code),
        ]);
    }

    public function customers(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json(['data' => []]);
        }

        return response()->json([
            'data' => $this->terminal->searchCustomers($q),
        ]);
    }

    public function storeCustomer(PosQuickCustomerRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->terminal->quickCreateCustomer(
                $request->validated('name'),
                $request->validated('phone'),
            ),
        ], 201);
    }

    public function openSession(PosOpenSessionRequest $request): JsonResponse
    {
        $register = PosRegister::query()->findOrFail($request->integer('register_id'));
        $session = $this->terminal->sessions()->open(
            $register,
            (string) $request->validated('opening_balance'),
            $request->validated('device_name'),
            $request->validated('opening_notes'),
        );

        return response()->json([
            'data' => $this->terminal->sessionPayload($session),
        ], 201);
    }

    public function sessionStatus(Request $request): JsonResponse
    {
        $bootstrap = $this->terminal->bootstrap($request->integer('register_id') ?: null);

        return response()->json([
            'data' => [
                'session' => $bootstrap['session'],
                'register' => $bootstrap['register'],
            ],
        ]);
    }

    public function cashIn(PosCashMovementRequest $request): JsonResponse
    {
        return $this->cashMovement($request, CashMovementType::CashIn, 'in');
    }

    public function cashOut(PosCashMovementRequest $request): JsonResponse
    {
        return $this->cashMovement($request, CashMovementType::CashOut, 'out');
    }

    public function checkout(PosCheckoutRequest $request): JsonResponse
    {
        $result = $this->terminal->checkout($request->validated());

        return response()->json(['data' => $result], 201);
    }

    public function suspend(PosSuspendRequest $request): JsonResponse
    {
        $register = PosRegister::query()->findOrFail($request->integer('register_id'));
        $guarded = $this->terminal->assertCanOperate($register);

        $sale = $this->terminal->salesEngine()->createDraftSale(
            SaleChannel::Pos,
            [
                'pos_register_id' => $guarded['register']->id,
                'cashier_session_id' => $guarded['session']->id,
                'branch_id' => $guarded['register']->branch_id,
                'customer_id' => $request->validated('customer_id'),
                'notes' => $request->validated('notes'),
                'discount_total' => $request->validated('discount_total') ?? '0',
            ],
            $this->terminal->buildSaleItems($request->validated('items') ?? []),
        );

        $suspended = $this->terminal->salesEngine()->suspend($sale);

        return response()->json([
            'data' => $this->terminal->salePayload($suspended->load(['items', 'customer'])),
        ], 201);
    }

    public function suspended(Request $request): JsonResponse
    {
        $register = PosRegister::query()->findOrFail($request->integer('register_id'));
        $guarded = $this->terminal->assertCanOperate($register);

        $sales = Sale::query()
            ->with(['items', 'customer'])
            ->where('cashier_session_id', $guarded['session']->id)
            ->where('is_suspended', true)
            ->latest('suspended_at')
            ->get()
            ->map(fn (Sale $sale) => $this->terminal->salePayload($sale))
            ->values();

        return response()->json(['data' => $sales]);
    }

    public function resume(Sale $sale): JsonResponse
    {
        $resumed = $this->terminal->salesEngine()->resume($sale);

        return response()->json([
            'data' => $this->terminal->salePayload($resumed->load(['items', 'customer'])),
        ]);
    }

    public function cancelSuspended(Request $request, Sale $sale): JsonResponse
    {
        $cancelled = $this->terminal->abandonPosSale(
            $sale,
            $request->string('reason')->toString() ?: null,
        );

        return response()->json([
            'data' => $this->terminal->salePayload($cancelled->load(['items', 'customer'])),
        ]);
    }

    public function closeSession(PosCloseSessionRequest $request): JsonResponse
    {
        $register = PosRegister::query()->findOrFail($request->integer('register_id'));
        $guarded = $this->terminal->assertCanOperate($register);

        $closed = $this->terminal->sessions()->close($guarded['session'], [
            'cash' => $request->validated('actual_cash'),
            'card' => $request->validated('actual_card'),
            'transfer' => $request->validated('actual_transfer'),
            'other' => $request->validated('actual_other'),
        ], $request->validated('closing_notes'), $request->validated('difference_reason'));

        return response()->json([
            'data' => [
                'session' => $this->terminal->sessionPayload($closed),
                'summary' => $this->terminal->reports()->shiftSummary($closed->fresh()),
            ],
        ]);
    }

    public function shiftSummary(Request $request): JsonResponse
    {
        $register = PosRegister::query()->findOrFail($request->integer('register_id'));
        $session = $register->sessions()
            ->where('user_id', auth('tenant')->id())
            ->latest('opened_at')
            ->firstOrFail();

        return response()->json([
            'data' => $this->terminal->reports()->shiftSummary($session),
        ]);
    }

    public function receipt(Sale $sale): View
    {
        $sale->load(['items', 'customer', 'branch', 'posRegister', 'cashierSession.user', 'salesInvoices']);

        return view('pos.receipt', [
            'sale' => $sale,
            'invoice' => $sale->salesInvoices->sortByDesc('id')->first(),
        ]);
    }

    private function cashMovement(PosCashMovementRequest $request, CashMovementType $type, string $direction): JsonResponse
    {
        $register = PosRegister::query()->findOrFail($request->integer('register_id'));
        $guarded = $this->terminal->assertCanOperate($register);

        $movement = $this->terminal->movements()->record(
            $guarded['session'],
            $type,
            Decimal::money($request->validated('amount')),
            [
                'direction' => $direction,
                'payment_method_type' => 'cash',
                'notes' => $request->validated('notes'),
                'reference' => $request->validated('reason'),
                'meta' => ['reason' => $request->validated('reason')],
            ]
        );

        return response()->json([
            'data' => [
                'id' => $movement->id,
                'type' => $movement->type->value,
                'amount' => (string) $movement->amount,
                'direction' => $movement->direction,
            ],
        ], 201);
    }
}
