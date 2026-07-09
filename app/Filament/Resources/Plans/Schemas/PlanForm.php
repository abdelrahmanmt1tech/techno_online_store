<?php

namespace App\Filament\Resources\Plans\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('dashboard.plan_details'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('name.ar')
                            ->label(__('dashboard.name_ar'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('name.en')
                            ->label(__('dashboard.name_en'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('title.ar')
                            ->label(__('dashboard.title_ar'))
                            ->maxLength(255),

                        TextInput::make('title.en')
                            ->label(__('dashboard.title_en'))
                            ->maxLength(255),

                        Textarea::make('description.ar')
                            ->label(__('dashboard.description_ar'))
                            ->rows(3),

                        Textarea::make('description.en')
                            ->label(__('dashboard.description_en'))
                            ->rows(3),
                    ])
                    ->columnSpanFull(),

                Section::make(__('dashboard.pricing'))
                    ->columns(3)
                    ->schema([
                        Select::make('type')
                            ->label(__('dashboard.type'))
                            ->options([
                                'commission' => __('dashboard.commission'),
                                'subscription' => __('dashboard.subscription'),
                            ])
                            ->required()
                            ->live()
                            ->native(false),

                        Select::make('currency')
                            ->label(__('dashboard.currency'))
                            ->options(fn () => array_merge(
                                [
                                    'SAR' => 'SAR (ريال سعودي)',
                                    'USD' => 'USD (دولار أمريكي)',
                                    'EUR' => 'EUR (يورو)',
                                    'GBP' => 'GBP (جنيه إسترليني)',
                                    'EGP' => 'EGP (جنيه مصري)',
                                    'AED' => 'AED (درهم إماراتي)',
                                ],
                                session()->get('plan_currencies', []),
                            ))
                            ->default('SAR')
                            ->searchable()
                            ->native(false)
                            ->suffixAction(
                                Action::make('add_currency')
                                    ->icon('heroicon-o-plus-circle')
                                    ->schema([
                                        Grid::make()
                                            ->columns(2)
                                            ->schema([
                                                TextInput::make('code')
                                                    ->label(__('dashboard.currency_code'))
                                                    ->required()
                                                    ->maxLength(3)
                                                    ->regex('/^[A-Z]+$/')
                                                    ->extraAttributes(['style' => 'text-transform: uppercase']),

                                                TextInput::make('name')
                                                    ->label(__('dashboard.currency_name'))
                                                    ->required()
                                                    ->maxLength(255),
                                            ]),
                                    ])
                                    ->action(function (array $data, $set) {
                                        $code = $data['code'];
                                        $name = $data['name'];

                                        $currencies = session()->get('plan_currencies', []);
                                        $currencies[$code] = "$code ($name)";
                                        session()->put('plan_currencies', $currencies);

                                        $set('currency', $code);
                                    }),
                            ),

                        TextInput::make('price')
                            ->label(__('dashboard.price'))
                            ->numeric()
                            ->prefix(fn (Get $get) => $get('currency') ?? 'SAR')
                            ->minValue(0)
                            ->live(),

                        TextInput::make('commission_per_order')
                            ->label(__('dashboard.commission_per_order'))
                            ->numeric()
                            ->prefix(fn (Get $get) => $get('currency') ?? 'SAR')
                            ->minValue(0)
                            ->live()
                            ->visible(fn (Get $get) => $get('type') === 'commission')
                            ->columnSpan(1),

                        Select::make('subscription_period')
                            ->label(__('dashboard.subscription_period'))
                            ->options([
                                'monthly' => __('dashboard.monthly'),
                                'yearly' => __('dashboard.yearly'),
                            ])
                            ->native(false)
                            ->visible(fn (Get $get) => $get('type') === 'subscription')
                            ->columnSpan(1),
                    ])
                    ->columnSpanFull(),

                Section::make(__('dashboard.settings'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('order')
                            ->label(__('dashboard.order'))
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_active')
                            ->label(__('dashboard.active'))
                            ->default(true),

                    ])
                    ->columnSpanFull(),

                Section::make(__('dashboard.plan_features'))
                    ->schema([
                        Repeater::make('features')
                            ->label(__('dashboard.plan_features'))
                            ->relationship('features')
                            ->reorderable(true)
                            ->collapsible()
                            ->defaultItems(1)
                            ->schema([
                                Grid::make()
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
                                        TextInput::make('order')
                                            ->label(__('dashboard.order'))
                                            ->numeric()
                                            ->default(0),
                                    ]),

                                Toggle::make('is_active')
                                    ->label(__('dashboard.active'))
                                    ->default(true),

                            ])
                            ->addActionLabel(__('dashboard.add_feature')),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
