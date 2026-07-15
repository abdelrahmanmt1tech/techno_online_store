<?php

namespace App\Filament\Tenant\Resources\Orders\Schemas;

use App\Models\Tenant\Coupon;
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
                            ->options(fn () => Governorate::where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $set('shipping_cost', Governorate::find($state)?->shipping_cost ?? 0);
                                self::recalculateTotals($get, $set);
                            }),

                        TextInput::make('shipping_cost')
                            ->label(__('dashboard.shipping_cost'))
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->reactive()
                            ->afterStateUpdated(fn ($get, $set) => self::recalculateTotals($get, $set)),

                        Select::make('coupon_id')
                            ->label(__('dashboard.coupon'))
                            ->options(fn () => Coupon::where('is_active', true)
                                ->orderBy('code')
                                ->get()
                                ->mapWithKeys(fn ($c) => [
                                    $c->id => $c->code.' — '.($c->type === 'percentage' ? $c->value.'%' : number_format($c->value, 2).' SAR'),
                                ]))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->nullable()
                            ->reactive()
                            ->afterStateUpdated(fn ($get, $set) => self::recalculateTotals($get, $set)),

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
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, $set) {
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
                                            }),

                                        Select::make('product_variant_id')
                                            ->label(__('dashboard.variant'))
                                            ->options(fn ($get) => ProductVariant::where('product_id', $get('product_id'))
                                                ->where('is_active', true)
                                                ->get()
                                                ->map(fn ($v) => [
                                                    'label' => $v->sku
                                                        ? $v->sku.' — '.$v->options->pluck('value')->implode(', ')
                                                        : $v->options->pluck('value')->implode(', '),
                                                    'value' => $v->id,
                                                ])
                                                ->pluck('label', 'value'))
                                            ->searchable()
                                            ->preload()
                                            ->nullable()
                                            ->reactive()
                                            ->visible(fn ($get) => filled($get('product_id')))
                                            ->afterStateUpdated(function ($state, $set) {
                                                if ($state) {
                                                    $variant = ProductVariant::find($state);
                                                    $set('unit_price', $variant?->sale_price ?? $variant?->price ?? 0);
                                                }
                                            }),

                                        TextInput::make('quantity')
                                            ->label(__('dashboard.quantity'))
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->required()
                                            ->reactive(),

                                        TextInput::make('unit_price')
                                            ->label(__('dashboard.unit_price'))
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->required()
                                            ->reactive()
                                            ->disabled(fn ($get) => filled($get('product_variant_id'))),
                                    ]),
                            ])
                            ->columns(1)
                            ->defaultItems(1)
                            ->addActionLabel(__('dashboard.add_item'))
                            ->reorderable(false)
                            ->live()
                            ->afterStateUpdated(fn ($state, $get, $set) => self::recalculateFromItems($state, $get, $set)),
                    ])
                    ->columnSpanFull(),

                Section::make(__('dashboard.pricing'))
                    ->columns(3)
                    ->schema([
                        TextInput::make('subtotal')
                            ->label(__('dashboard.subtotal'))
                            ->numeric()
                            ->default(0)
                            ->disabled(),

                        TextInput::make('discount')
                            ->label(__('dashboard.discount'))
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->reactive()
                            ->afterStateUpdated(fn ($get, $set) => self::recalculateTotals($get, $set)),

                        TextInput::make('total')
                            ->label(__('dashboard.total'))
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private static function recalculateFromItems($items, $get, $set): void
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0);
        }

        $discount = self::calculateDiscount($subtotal, $get);
        $shippingCost = (float) ($get('shipping_cost') ?? 0);
        $total = max(0, $subtotal - $discount + $shippingCost);

        $set('subtotal', round($subtotal, 2));
        $set('discount', $discount);
        $set('total', round($total, 2));
    }

    private static function recalculateTotals($get, $set): void
    {
        $items = $get('items_data') ?? [];

        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0);
        }

        $discount = self::calculateDiscount($subtotal, $get);
        $shippingCost = (float) ($get('shipping_cost') ?? 0);
        $total = max(0, $subtotal - $discount + $shippingCost);

        $set('subtotal', round($subtotal, 2));
        $set('discount', $discount);
        $set('total', round($total, 2));
    }

    private static function calculateDiscount(float $subtotal, $get): float
    {
        $couponId = $get('coupon_id');
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
