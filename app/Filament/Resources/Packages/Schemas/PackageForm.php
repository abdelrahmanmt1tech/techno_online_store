<?php

namespace App\Filament\Resources\Packages\Schemas;

use App\Models\Country;
use App\Models\Currency;
use App\Support\Modules\TenantModule;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('dashboard.package_details'))
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

                        TextInput::make('desc.ar')
                            ->label(__('dashboard.description_ar'))
                            ->maxLength(255),

                        TextInput::make('desc.en')
                            ->label(__('dashboard.description_en'))
                            ->maxLength(255),

                        Toggle::make('is_full_package')
                            ->label(__('dashboard.full_package'))
                            ->live()
                            ->columnSpanFull(),

                        Select::make('module')
                            ->label(__('dashboard.module'))
                            ->options(fn () => collect(TenantModule::cases())
                                ->mapWithKeys(fn (TenantModule $module) => [
                                    $module->value => $module->label(),
                                ]))
                            ->native(false)
                            ->hidden(fn (Get $get) => (bool) $get('is_full_package'))
                            ->columnSpan(1),

                        TextInput::make('trials_duration')
                            ->label(__('dashboard.trials_duration'))
                            ->numeric()
                            ->minValue(0)
                            ->default(14)
                            ->columnSpan(1),

                        TextInput::make('sort')
                            ->label(__('dashboard.sort_order'))
                            ->numeric()
                            ->default(0)
                            ->columnSpan(1),

                        Toggle::make('is_active')
                            ->label(__('dashboard.active'))
                            ->default(true)
                            ->columnSpan(1),

                        FileUpload::make('image')
                            ->label(__('dashboard.image'))
                            ->image()
                            ->directory('packages')
                            ->columnSpan(3),
                    ])
                    ->columnSpanFull(),

                Section::make(__('dashboard.package_prices'))
                    ->schema([
                        Repeater::make('prices')
                            ->label(__('dashboard.package_prices'))
                            ->relationship('prices')
                            ->reorderable(false)
                            ->collapsible()
                            ->defaultItems(1)
                            ->rules([
                                function (): Closure {
                                    return function (string $attribute, mixed $value, Closure $fail): void {
                                        if (! collect($value ?? [])->contains(fn (array $item) => (bool) ($item['is_default'] ?? false))) {
                                            $fail(__('dashboard.at_least_one_default_required'));
                                        }
                                    };
                                },
                            ])
                            ->schema([
                                Grid::make()
                                    ->columns(4)
                                    ->schema([
                                        Select::make('country_id')
                                            ->label(__('dashboard.country'))
                                            ->options(fn () => Country::where('is_active', true)
                                                ->orderBy('sort_order')
                                                ->get()
                                                ->mapWithKeys(fn ($country) => [
                                                    $country->id => $country->getTranslation('name', app()->getLocale()),
                                                ]))
                                            ->searchable()
                                            ->native(false)
                                            ->required()
                                            ->live()
                                            ->disableOptionWhen(function (string $value, Get $get): bool {
                                                $currentCountryId = $get('country_id');

                                                $selectedCountries = collect($get('../') ?? [])
                                                    ->pluck('country_id')
                                                    ->filter()
                                                    ->unique()
                                                    ->map(fn ($id) => (string) $id)
                                                    ->all();

                                                return in_array($value, $selectedCountries, true)
                                                    && $value !== (string) $currentCountryId;
                                            })
                                            ->afterStateUpdated(function ($state, $set) {
                                                $currency = Country::find($state)?->currency;

                                                if ($currency) {
                                                    $set('currency_id', $currency->id);
                                                }
                                            })
                                            ->suffixAction(self::addCountryAction()),

                                        Select::make('currency_id')
                                            ->label(__('dashboard.currency'))
                                            ->options(fn () => Currency::where('is_active', true)
                                                ->orderBy('sort_order')
                                                ->get()
                                                ->mapWithKeys(fn ($currency) => [
                                                    $currency->id => $currency->code.' ('.$currency->getTranslation('name', app()->getLocale()).')',
                                                ]))
                                            ->searchable()
                                            ->native(false)
                                            ->required(),
                                        // ->suffixAction(self::addCurrencyAction()),

                                        TextInput::make('price_monthly')
                                            ->label(__('dashboard.price_monthly'))
                                            ->numeric()
                                            ->prefix(fn (Get $get) => Currency::find($get('currency_id'))?->code)
                                            ->minValue(0)
                                            ->required(),

                                        TextInput::make('price_yearly')
                                            ->label(__('dashboard.price_yearly'))
                                            ->numeric()
                                            ->prefix(fn (Get $get) => Currency::find($get('currency_id'))?->code)
                                            ->minValue(0)
                                            ->required(),

                                        Toggle::make('is_default')
                                            ->label(__('dashboard.is_default'))
                                            ->default(false)
                                            ->fixIndistinctState()
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->addActionLabel(__('dashboard.add_price')),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private static function addCountryAction(): Action
    {
        return Action::make('add_country')
            ->icon('heroicon-o-plus-circle')
            ->schema([
                Grid::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name_ar')
                            ->label(__('dashboard.name_ar'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('name_en')
                            ->label(__('dashboard.name_en'))
                            ->required()
                            ->maxLength(255),
                    ]),
            ])
            ->action(function (array $data, $set) {
                $country = Country::firstOrCreate(
                    ['name' => ['ar' => $data['name_ar'], 'en' => $data['name_en']]],
                    [
                        'is_active' => true,
                        'sort_order' => 0,
                    ]
                );

                $set('country_id', $country->id);
            });
    }

    private static function addCurrencyAction(): Action
    {
        return Action::make('add_currency')
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
                $currency = Currency::firstOrCreate(
                    ['code' => $data['code']],
                    [
                        'name' => ['ar' => $data['name'], 'en' => $data['name']],
                        'is_active' => true,
                    ]
                );

                $set('currency_id', $currency->id);
            });
    }
}
