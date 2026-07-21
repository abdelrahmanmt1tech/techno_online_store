<?php

namespace App\Filament\Resources\Currencies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CurrencyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('dashboard.currency_details'))
                    ->columns(3)
                    ->schema([
                        TextInput::make('name.ar')
                            ->label(__('dashboard.name_ar'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('name.en')
                            ->label(__('dashboard.name_en'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('code')
                            ->label(__('dashboard.currency_code'))
                            ->required()
                            ->maxLength(10)
                            ->unique(ignoreRecord: true),

                        TextInput::make('symbol')
                            ->label(__('dashboard.currency_symbol'))
                            ->maxLength(10),

                        TextInput::make('sort_order')
                            ->label(__('dashboard.sort_order'))
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        Toggle::make('is_active')
                            ->label(__('dashboard.active'))
                            ->default(true),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
