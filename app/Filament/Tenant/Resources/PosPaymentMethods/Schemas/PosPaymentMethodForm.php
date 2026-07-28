<?php

namespace App\Filament\Tenant\Resources\PosPaymentMethods\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PosPaymentMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('erp.sections.details'))
                ->columns(3)
                ->schema([
                    TextInput::make('name')->label(__('erp.fields.name'))->required()->maxLength(255),
                    TextInput::make('code')->label(__('erp.fields.code'))->required()->maxLength(50)->unique(ignoreRecord: true),
                    Select::make('type')
                        ->label(__('commerce.fields.type'))
                        ->options([
                            'cash' => __('commerce.pos_payment_types.cash'),
                            'card' => __('commerce.pos_payment_types.card'),
                            'transfer' => __('commerce.pos_payment_types.transfer'),
                            'other' => __('commerce.pos_payment_types.other'),
                        ])
                        ->default('cash')
                        ->required()
                        ->native(false),
                    TextInput::make('sort_order')->label(__('commerce.fields.sort_order'))->numeric()->default(0),
                    Toggle::make('is_active')->label(__('erp.fields.is_active'))->default(true),
                    Toggle::make('opens_cash_drawer')->label(__('commerce.fields.opens_cash_drawer'))->default(false),
                ])
                ->columnSpanFull(),
        ]);
    }
}
