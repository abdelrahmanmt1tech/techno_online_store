<?php

namespace App\Filament\Tenant\Resources\Orders\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('dashboard.order_details'))
                    ->schema([
                        TextEntry::make('order_number')
                            ->label(__('dashboard.order_number'))
                            ->weight('bold')
                            ->size(TextSize::Large),

                        TextEntry::make('status')
                            ->label(__('dashboard.status'))
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'gray',
                                'confirmed' => 'info',
                                'processing' => 'primary',
                                'shipped' => 'warning',
                                'delivered' => 'success',
                                'cancelled' => 'danger',
                                'returned' => 'danger',
                                default => 'gray',
                            }),

                        TextEntry::make('created_at')
                            ->label(__('dashboard.created_at'))
                            ->dateTime('Y-m-d H:i'),
                    ])->columns(3),

                Section::make(__('dashboard.customer_info'))
                    ->schema([
                        TextEntry::make('customer_name')
                            ->label(__('dashboard.customer_name')),

                        TextEntry::make('customer_phone')
                            ->label(__('dashboard.customer_phone')),

                        TextEntry::make('customer_email')
                            ->label(__('dashboard.customer_email')),

                        TextEntry::make('customer_address')
                            ->label(__('dashboard.customer_address'))
                            ->columnSpanFull(),

                        TextEntry::make('governorate_name')
                            ->label(__('dashboard.governorate')),

                        TextEntry::make('shipping_cost')
                            ->label(__('dashboard.shipping_cost'))
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 2).' SAR'),

                        TextEntry::make('notes')
                            ->label(__('dashboard.notes'))
                            ->columnSpanFull(),
                    ])->columns(3),

                Section::make(__('dashboard.order_items'))
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                TextEntry::make('product_name')
                                    ->label(__('dashboard.product'))
                                    ->weight('bold'),

                                TextEntry::make('variant_options')
                                    ->label(__('dashboard.options'))
                                    ->formatStateUsing(fn ($state) => $state ? collect($state)->implode(', ') : '-'),

                                TextEntry::make('quantity')
                                    ->label(__('dashboard.quantity')),

                                TextEntry::make('unit_price')
                                    ->label(__('dashboard.unit_price'))
                                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2)),

                                TextEntry::make('total_price')
                                    ->label(__('dashboard.total'))
                                    ->weight('bold')
                                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2)),
                            ])
                            ->columns(5)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('dashboard.pricing_summary'))
                    ->schema([
                        TextEntry::make('items_count')
                            ->label(__('dashboard.items_count'))
                            ->state(fn ($record) => $record->items->count()),

                        TextEntry::make('items_quantity')
                            ->label(__('dashboard.total_quantity'))
                            ->state(fn ($record) => $record->items->sum('quantity')),

                        TextEntry::make('subtotal')
                            ->label(__('dashboard.subtotal'))
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 2).' SAR'),

                        TextEntry::make('discount')
                            ->label(__('dashboard.discount'))
                            ->formatStateUsing(fn ($state) => ($state > 0 ? '-' : '').number_format((float) $state, 2).' SAR')
                            ->color(fn ($state) => (float) $state > 0 ? 'danger' : null),

                        TextEntry::make('shipping_cost_summary')
                            ->label(__('dashboard.shipping_cost'))
                            ->state(fn ($record) => $record->shipping_cost)
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 2).' SAR')
                            ->color(fn ($state) => (float) $state > 0 ? 'warning' : null),

                        TextEntry::make('total')
                            ->label(__('dashboard.total'))
                            ->weight('bold')
                            ->size(TextSize::Large)
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 2).' SAR')
                            ->color('success'),
                    ])->columns(3),
            ]);
    }
}
