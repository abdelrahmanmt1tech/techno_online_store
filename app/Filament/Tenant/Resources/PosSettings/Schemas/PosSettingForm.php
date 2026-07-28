<?php

namespace App\Filament\Tenant\Resources\PosSettings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PosSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('erp.sections.details'))
                ->columns(2)
                ->schema([
                    Select::make('receipt_number_strategy')
                        ->label(__('commerce.fields.receipt_number_strategy'))
                        ->options([
                            'per_register' => __('commerce.receipt_number_strategies.per_register'),
                            'global' => __('commerce.receipt_number_strategies.global'),
                        ])
                        ->default('per_register')
                        ->required()
                        ->native(false),
                    TextInput::make('default_currency')->label(__('commerce.fields.default_currency'))->maxLength(8),
                    Toggle::make('require_open_session')->label(__('commerce.fields.require_open_session'))->default(true),
                    Toggle::make('allow_suspend_sales')->label(__('commerce.fields.allow_suspend_sales'))->default(true),
                    Toggle::make('allow_negative_stock')->label(__('commerce.fields.allow_negative_stock'))->default(false),
                ])
                ->columnSpanFull(),
        ]);
    }
}
