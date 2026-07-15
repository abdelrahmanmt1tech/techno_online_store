<?php

namespace App\Filament\Tenant\Resources\Coupons\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('dashboard.coupon_details'))
                    ->columns(3)
                    ->schema([
                        TextInput::make('code')
                            ->label(__('dashboard.coupon_code'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Select::make('type')
                            ->label(__('dashboard.coupon_type'))
                            ->options([
                                'percentage' => __('dashboard.percentage'),
                                'fixed' => __('dashboard.fixed_amount'),
                            ])
                            ->required()
                            ->native(false),

                        TextInput::make('value')
                            ->label(__('dashboard.coupon_value'))
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->suffix(fn ($get) => $get('type') === 'percentage' ? '%' : ' '),

                        TextInput::make('minimum_order_amount')
                            ->label(__('dashboard.minimum_order_amount'))
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        TextInput::make('maximum_discount_amount')
                            ->label(__('dashboard.maximum_discount_amount'))
                            ->numeric()
                            ->minValue(0)
                            ->visible(fn ($get) => $get('type') === 'percentage'),

                        Toggle::make('is_active')
                            ->label(__('dashboard.active'))
                            ->default(true),

                    ])
                    ->columnSpanFull(),

                Section::make(__('dashboard.usage_limits'))
                    ->columns(4)
                    ->schema([
                        TextInput::make('usage_limit')
                            ->label(__('dashboard.usage_limit'))
                            ->numeric()
                            ->minValue(0)
                            ->nullable(),

                        TextInput::make('per_user_limit')
                            ->label(__('dashboard.per_user_limit'))
                            ->numeric()
                            ->minValue(0)
                            ->nullable(),

                        DateTimePicker::make('starts_at')
                            ->label(__('dashboard.starts_at')),

                        DateTimePicker::make('expires_at')
                            ->label(__('dashboard.expires_at')),

                    ])
                    ->columnSpanFull(),
            ]);
    }
}
