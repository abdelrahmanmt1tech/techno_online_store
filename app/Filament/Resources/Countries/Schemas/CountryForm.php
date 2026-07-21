<?php

namespace App\Filament\Resources\Countries\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CountryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('dashboard.country_details'))
                    ->columns(4)
                    ->schema([
                        TextInput::make('name.ar')
                            ->label(__('dashboard.name_ar'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('name.en')
                            ->label(__('dashboard.name_en'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('country_code')
                            ->label(__('dashboard.country_code'))
                            ->maxLength(2)
                            ->unique(ignoreRecord: true),

                        TextInput::make('currency_name.ar')
                            ->label(__('dashboard.currency_name_ar')),

                        TextInput::make('currency_name.en')
                            ->label(__('dashboard.currency_name_en')),

                        TextInput::make('currency_symbol')
                            ->label(__('dashboard.currency_symbol')),

                        TextInput::make('phone_code')
                            ->label(__('dashboard.phone_code')),

                        FileUpload::make('icon')
                            ->label(__('dashboard.icon'))
                            ->image()
                            ->directory('countries'),

                        Select::make('locale')
                            ->label(__('dashboard.locale'))
                            ->options([
                                'ar' => 'العربية',
                                'en' => 'English',
                            ]),

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
