<?php

namespace App\Filament\Tenant\Resources\Customers\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('dashboard.customer'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('dashboard.name'))
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columnSpanFull(),

                Section::make(__('dashboard.customer_contacts'))
                    ->schema([
                        Repeater::make('contacts')
                            ->label('')
                            ->relationship()
                            ->schema([
                                Grid::make(3)->schema([
                                    Select::make('type')
                                        ->label(__('dashboard.contact_type'))
                                        ->options([
                                            'phone' => __('dashboard.customer_phone'),
                                            'email' => __('dashboard.customer_email'),
                                            'whatsapp' => __('dashboard.whatsapp'),
                                        ])
                                        ->required()
                                        ->native(false),

                                    TextInput::make('value')
                                        ->label(__('dashboard.contact_value'))
                                        ->required()
                                        ->maxLength(255),

                                    Toggle::make('is_primary')
                                        ->label(__('dashboard.primary'))
                                        ->default(false),
                                ]),
                            ])
                            ->columns(1)
                            ->defaultItems(0)
                            ->default([
                                ['type' => 'phone', 'is_primary' => true],
                                ['type' => 'email', 'is_primary' => true],
                                ['type' => 'whatsapp', 'is_primary' => true],
                            ])
                            ->addActionLabel(__('dashboard.add_contact'))
                            ->reorderable(false)
                            ->collapsible()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
