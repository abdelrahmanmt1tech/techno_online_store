<?php

namespace App\Filament\Tenant\Resources\Orders\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('dashboard.order_details'))
                    ->schema([
                        TextEntry::make('order_number')
                            ->label(__('dashboard.order_number')),

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
                            ->label(__('dashboard.customer_address')),

                        TextEntry::make('governorate_name')
                            ->label(__('dashboard.governorate')),

                        TextEntry::make('notes')
                            ->label(__('dashboard.notes')),
                    ])->columns(3),

                Section::make(__('dashboard.pricing'))
                    ->schema([
                        TextEntry::make('subtotal')
                            ->label(__('dashboard.subtotal')),

                        TextEntry::make('discount')
                            ->label(__('dashboard.discount')),

                        TextEntry::make('shipping_cost')
                            ->label(__('dashboard.shipping_cost')),

                        TextEntry::make('total')
                            ->label(__('dashboard.total'))
                            ->weight('bold'),
                    ])->columns(4),

                Section::make(__('dashboard.order_items'))
                    ->schema([
                        TextEntry::make('items.product_name')
                            ->label(__('dashboard.product'))
                            ->placeholder('-'),

                        TextEntry::make('items.quantity')
                            ->label(__('dashboard.quantity'))
                            ->placeholder('-'),

                        TextEntry::make('items.unit_price')
                            ->label(__('dashboard.unit_price'))
                            ->placeholder('-'),

                        TextEntry::make('items.total_price')
                            ->label(__('dashboard.total'))
                            ->placeholder('-'),

                        TextEntry::make('items.variant_options')
                            ->label(__('dashboard.options'))
                            ->formatStateUsing(fn ($state) => $state ? collect($state)->implode(', ') : '-')
                            ->placeholder('-'),
                    ])->columns(5),
            ]);
    }
}
