<?php

namespace App\Filament\Tenant\Resources\Governorates\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GovernorateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('dashboard.governorate_details'))
                    ->columns(3)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('dashboard.name'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('shipping_cost')
                            ->label(__('dashboard.shipping_cost'))
                            ->required()
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
