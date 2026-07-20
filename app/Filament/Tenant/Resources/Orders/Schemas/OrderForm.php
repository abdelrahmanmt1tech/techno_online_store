<?php

namespace App\Filament\Tenant\Resources\Orders\Schemas;

use App\Models\Tenant\Coupon;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Governorate;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('dashboard.customer_info'))
                    ->columns(3)
                    ->schema([
                        Select::make('customer_id')
                            ->label(__('dashboard.customer'))
                            ->options(fn () => Customer::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if (! $state) {
                                    $set('customer_name', null);
                                    $set('customer_phone', null);
                                    $set('customer_email', null);
                                    return;
                                }
                                $customer = Customer::with('contacts')->find($state);
                                if (! $customer) return;

                                $set('customer_name', $customer->name);
                                $set('customer_phone', $customer->primaryPhone());
                                $set('customer_email', $customer->primaryEmail());
                            })
                            ->suffixAction(
                                \Filament\Actions\Action::make('createCustomer')
                                    ->icon('heroicon-o-plus')
                                    ->modalHeading(__('dashboard.create_customer'))
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('name')
                                                ->label(__('dashboard.customer_name'))
                                                ->required()
                                                ->maxLength(255),
                                            TextInput::make('phone')
                                                ->label(__('dashboard.customer_phone'))
                                                ->required()
                                                ->maxLength(255),
                                            TextInput::make('email')
                                                ->label(__('dashboard.customer_email'))
                                                ->email()
                                                ->maxLength(255),
                                        ]),
                                    ])
                                    ->action(function (array $data, $set) {
                                        $customer = Customer::create(['name' => $data['name']]);

                                        $customer->contacts()->create([
                                            'type' => 'phone',
                                            'value' => $data['phone'],
                                            'is_primary' => true,
                                        ]);

                                        if (! empty($data['email'])) {
                                            $customer->contacts()->create([
                                                'type' => 'email',
                                                'value' => $data['email'],
                                                'is_primary' => true,
                                            ]);

                                            $customer->contacts()->create([
                                                'type' => 'whatsapp',
                                                'value' => $data['phone'],
                                                'is_primary' => true,
                                            ]);
                                        }

                                        $set('customer_id', $customer->id);
                                        $set('customer_name', $customer->name);
                                        $set('customer_phone', $data['phone']);
                                        $set('customer_email', $data['email'] ?? null);
                                    })
                            ),

                        TextInput::make('customer_name')
                            ->label(__('dashboard.customer_name'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('customer_phone')
                            ->label(__('dashboard.customer_phone'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('customer_email')
                            ->label(__('dashboard.customer_email'))
                            ->email()
                            ->maxLength(255),

                        Textarea::make('customer_address')
                            ->label(__('dashboard.customer_address'))
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),

                        Select::make('governorate_id')
                            ->label(__('dashboard.governorate'))
                            ->required()
                            ->options(fn() => Governorate::where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $set('shipping_cost', Governorate::find($state)?->shipping_cost ?? 0);
                                self::recalculate($get, $set);
                            }),

                        TextInput::make('shipping_cost')
                            ->label(__('dashboard.shipping_cost'))
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->minValue(0)
                            ->live()
                            ->afterStateUpdated(fn($get, $set) => self::recalculate($get, $set)),

                        Select::make('coupon_id')
                            ->label(__('dashboard.coupon'))
                            ->options(fn() => Coupon::where('is_active', true)
                                ->orderBy('code')
                                ->get()
                                ->mapWithKeys(fn($c) => [
                                    $c->id => $c->code . ' — ' . ($c->type === 'percentage' ? $c->value . '%' : number_format($c->value, 2) . ' SAR'),
                                ]))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(fn($get, $set) => self::recalculate($get, $set)),

                        Select::make('status')
                            ->label(__('dashboard.status'))
                            ->options([
                                'pending' => __('dashboard.pending'),
                                'confirmed' => __('dashboard.confirmed'),
                                'processing' => __('dashboard.processing'),
                                'shipped' => __('dashboard.shipped'),
                                'delivered' => __('dashboard.delivered'),
                                'cancelled' => __('dashboard.cancelled'),
                            ])
                            ->default('pending')
                            ->required()
                            ->native(false),

                        Textarea::make('notes')
                            ->label(__('dashboard.notes'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make(__('dashboard.order_items'))
                    ->schema([
                        Repeater::make('items_data')
                            ->label('')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        Select::make('product_id')
                                            ->label(__('dashboard.product'))
                                            ->options(fn () => Product::where('is_active', true)
                                                ->orderBy('name')
                                                ->pluck('name', 'id'))
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                $set('product_variant_id', null);
                                                $variant = ProductVariant::where('product_id', $state)
                                                    ->where('is_active', true)
                                                    ->first();
                                                if ($variant) {
                                                    $set('unit_price', $variant->sale_price ?? $variant->price);
                                                } else {
                                                    $product = Product::find($state);
                                                    $set('unit_price', $product?->sale_price ?? $product?->price ?? 0);
                                                }

                                                self::recalculate($get, $set, nested: true);
                                            }),

                                        Select::make('product_variant_id')
                                            ->label(__('dashboard.variant'))
                                            
                                            ->options(function ($get) {
                                                $currentVariantId = $get('product_variant_id');

                                                $selectedVariantIds = collect($get('../../items_data') ?? [])
                                                    ->pluck('product_variant_id')
                                                    ->filter(fn($id) => filled($id) && $id != $currentVariantId)
                                                    ->values()
                                                    ->all();

                                                return ProductVariant::where('product_id', $get('product_id'))
                                                    ->where('is_active', true)
                                                    ->whereNotIn('id', $selectedVariantIds)
                                                    ->get()
                                                    ->map(fn($v) => [
                                                        'label' => $v->sku
                                                            ? $v->sku . ' — ' . $v->options->pluck('value')->implode(', ')
                                                            : $v->options->pluck('value')->implode(', '),
                                                        'value' => $v->id,
                                                    ])
                                                    ->pluck('label', 'value');
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->live()
                                            ->visible(fn($get) => filled($get('product_id')))
                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                if ($state) {
                                                    $variant = ProductVariant::find($state);
                                                    $set('unit_price', $variant?->sale_price ?? $variant?->price ?? 0);
                                                }
                                                self::recalculate($get, $set, nested: true);
                                            }),

                                        TextInput::make('quantity')
                                            ->label(__('dashboard.quantity'))
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn($get, $set) => self::recalculate($get, $set, nested: true)),

                                        TextInput::make('unit_price')
                                            ->label(__('dashboard.unit_price'))
                                            ->numeric()
                                            ->default(0)
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn($get, $set) => self::recalculate($get, $set, nested: true)),
                                    ]),
                            ])
                            ->columns(1)
                            ->defaultItems(1)
                            ->addActionLabel(__('dashboard.add_item'))
                            ->reorderable(false)
                            ->live()
                            ->afterStateUpdated(fn($get, $set) => self::recalculate($get, $set)),
                    ])
                    ->columnSpanFull(),

                Section::make(__('dashboard.pricing'))
                    ->columns(3)
                    ->schema([
                        TextInput::make('subtotal')
                            ->label(__('dashboard.subtotal'))
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('discount')
                            ->label(__('dashboard.discount'))
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('total')
                            ->label(__('dashboard.total'))
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * دالة الحساب الموحدة. لو الاستدعاء جاي من جوه عنصر في الـ Repeater
     * (يعني من quantity/unit_price/product_id/product_variant_id) لازم
     * تبعت $nested = true عشان تطلع مستويين للبرة (../../) وتوصل
     * لحقول items_data / coupon_id / shipping_cost / subtotal / total
     * اللي عايشة برة الـ Repeater.
     */
    private static function recalculate($get, $set, bool $nested = false): void
    {
        $prefix = $nested ? '../../' : '';

        $items = $get($prefix . 'items_data') ?? [];
        $couponId = $get($prefix . 'coupon_id');
        $shippingCost = (float) ($get($prefix . 'shipping_cost') ?? 0);

        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0);
        }

        $discount = self::calculateDiscount($subtotal, $couponId);
        $total = max(0, $subtotal - $discount + $shippingCost);

        $set($prefix . 'subtotal', round($subtotal, 2));
        $set($prefix . 'discount', $discount);
        $set($prefix . 'total', round($total, 2));
    }

    private static function calculateDiscount(float $subtotal, $couponId): float
    {
        if (! $couponId) {
            return 0;
        }

        $coupon = Coupon::find($couponId);
        if (! $coupon || ! $coupon->isValid()) {
            return 0;
        }

        $discount = 0;
        if ($coupon->type === 'percentage') {
            $discount = $subtotal * ($coupon->value / 100);
            if ($coupon->maximum_discount_amount !== null) {
                $discount = min($discount, $coupon->maximum_discount_amount);
            }
        } else {
            $discount = min($coupon->value, $subtotal);
        }

        return round($discount, 2);
    }
}