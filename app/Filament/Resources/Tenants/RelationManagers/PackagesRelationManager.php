<?php

namespace App\Filament\Resources\Tenants\RelationManagers;

use App\Models\Currency;
use App\Models\Package;
use App\Models\PackagePrice;
use Carbon\Carbon;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PackagesRelationManager extends RelationManager
{
    protected static string $relationship = 'packages';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('dashboard.packages');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.package');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.packages');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->columns(3)
                    ->schema([
                        Select::make('package_id')
                            ->label(__('dashboard.package'))
                            ->options(fn () => Package::where('is_active', true)
                                ->orderBy('sort')
                                ->get()
                                ->mapWithKeys(fn ($package) => [
                                    $package->id => $package->getTranslation('name', app()->getLocale()),
                                ]))
                            ->searchable()
                            ->native(false)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $package = Package::with('prices')->find($state);

                                if (! $package) {
                                    return;
                                }

                                $tenant = $this->getOwnerRecord();

                                $candidatePrices = $package->prices
                                    ->when(
                                        $tenant?->country_id,
                                        fn ($prices) => $prices->where('country_id', $tenant->country_id),
                                    );

                                if ($candidatePrices->isEmpty()) {
                                    $candidatePrices = $package->prices;
                                }

                                $packagePrice = $candidatePrices->sortByDesc('is_default')->first();

                                $startedAt = $get('started_at') ? Carbon::parse($get('started_at')) : now();
                                $period = $get('period') ?? 'monthly';
                                $durationType = $period === 'yearly' ? 'year' : 'month';

                                $trialEndsAt = $package->trials_duration
                                    ? $startedAt->copy()->addDays($package->trials_duration)
                                    : null;

                                $set('trial_ends_at', $trialEndsAt?->toDateTimeString());

                                $set('price_option', $packagePrice?->id);

                                if ($packagePrice) {
                                    $set('price', $period === 'yearly' ? $packagePrice->price_yearly : $packagePrice->price_monthly);
                                    $set('currency_id', $packagePrice->currency_id);
                                    $set('duration', 1);
                                    $set('duration_type', $durationType);
                                    $set('expires_at', $this->resolveExpiry(
                                        $trialEndsAt ?? $startedAt,
                                        1,
                                        $durationType,
                                    ));
                                }
                            })
                            ->columnSpan(1),

                        Select::make('price_option')
                            ->label(__('dashboard.package_price'))
                            ->options(fn (Get $get) => $this->getPriceOptions($get))
                            ->searchable()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn ($state, $set, $get) => $this->applyPriceOption($state, $set, $get))
                            ->afterStateHydrated(function (Select $component, $record): void {
                                if (! $record) {
                                    return;
                                }

                                $package = Package::with('prices')->find($record->package_id);

                                if (! $package) {
                                    return;
                                }

                                $tenant = $this->getOwnerRecord();

                                $candidatePrices = $package->prices
                                    ->when(
                                        $tenant?->country_id,
                                        fn ($prices) => $prices->where('country_id', $tenant->country_id),
                                    );

                                if ($candidatePrices->isEmpty()) {
                                    $candidatePrices = $package->prices;
                                }

                                $packagePrice = $candidatePrices
                                    ->first(fn (PackagePrice $price) => $price->currency_id === $record->currency_id)
                                    ?? $candidatePrices->sortByDesc('is_default')->first();

                                $component->state($packagePrice?->id);
                            })
                            ->columnSpan(1),

                        TextInput::make('price')
                            ->label(__('dashboard.price'))
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->columnSpan(1),

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
                            ->columnSpan(1),

                        Select::make('period')
                            ->label(__('dashboard.period'))
                            ->options([
                                'monthly' => __('dashboard.monthly'),
                                'yearly' => __('dashboard.yearly'),
                            ])
                            ->native(false)
                            ->default(fn ($record) => $record?->duration_type === 'year' ? 'yearly' : 'monthly')
                            ->afterStateHydrated(fn ($component, $record) => $component->state($record?->duration_type === 'year' ? 'yearly' : 'monthly'))
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($state, $set, $get) => $this->applyPriceOption($get('price_option'), $set, $get))
                            ->columnSpan(1),

                        DateTimePicker::make('started_at')
                            ->label(__('dashboard.started_at'))
                            ->default(now())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($state, $set, $get) => $this->syncExpiry($set, $get))
                            ->columnSpan(1),

                        DateTimePicker::make('trial_ends_at')
                            ->label(__('dashboard.trial_ends_at'))
                            ->live()
                            ->afterStateUpdated(fn ($state, $set, $get) => $this->syncExpiry($set, $get))
                            ->columnSpan(1),

                        DateTimePicker::make('expires_at')
                            ->label(__('dashboard.expires_at'))
                            ->columnSpan(1),

                        Hidden::make('duration'),
                        Hidden::make('duration_type'),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private function getPriceOptions(Get $get): array
    {
        $packageId = $get('package_id');

        if (! $packageId) {
            return [];
        }

        $package = Package::with('prices.currency')->find($packageId);

        if (! $package) {
            return [];
        }

        $tenant = $this->getOwnerRecord();

        $prices = $package->prices
            ->when(
                $tenant?->country_id,
                fn ($prices) => $prices->where('country_id', $tenant->country_id),
            );

        if ($prices->isEmpty()) {
            $prices = $package->prices;
        }

        return $prices
            ->sortByDesc('is_default')
            ->mapWithKeys(function (PackagePrice $price) {
                $label = number_format((float) $price->price_monthly, 2).' '.__('dashboard.monthly')
                    .' / '.number_format((float) $price->price_yearly, 2).' '.__('dashboard.yearly');

                if ($price->currency) {
                    $label .= ' - '.$price->currency->code;
                }

                return [$price->id => $label];
            })
            ->all();
    }

    private function applyPriceOption(mixed $state, $set, $get): void
    {
        if (! $state) {
            return;
        }

        $price = PackagePrice::find($state);

        if (! $price) {
            return;
        }

        $period = $get('period') ?? 'monthly';

        $set('price', $period === 'yearly' ? $price->price_yearly : $price->price_monthly);
        $set('currency_id', $price->currency_id);

        $this->syncExpiry($set, $get);
    }

    private function resolveExpiry(Carbon $startedAt, int $duration, string $durationType): string
    {
        $expiry = match ($durationType) {
            'day' => $startedAt->copy()->addDays($duration),
            'year' => $startedAt->copy()->addYears($duration),
            default => $startedAt->copy()->addMonths($duration),
        };

        return $expiry->toDateTimeString();
    }

    private function syncExpiry($set, $get): void
    {
        $startedAt = $get('started_at');
        $trialEndsAt = $get('trial_ends_at');
        $period = $get('period') ?? 'monthly';

        if (! $startedAt) {
            return;
        }

        $durationType = $period === 'yearly' ? 'year' : 'month';
        $base = $trialEndsAt ? Carbon::parse($trialEndsAt) : Carbon::parse($startedAt);

        $set('duration', 1);
        $set('duration_type', $durationType);
        $set('expires_at', $this->resolveExpiry(
            $base,
            1,
            $durationType,
        ));
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('package_id')
            ->columns([
                TextColumn::make('package.name')
                    ->label(__('dashboard.package'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('price')
                    ->label(__('dashboard.price'))
                    ->money(fn ($record) => $record->currency?->code ?? 'SAR')
                    ->sortable(),

                TextColumn::make('duration')
                    ->label(__('dashboard.duration'))
                    ->formatStateUsing(fn ($state, $record) => $state.' '.__("dashboard.{$record->duration_type}"))
                    ->sortable(),

                TextColumn::make('started_at')
                    ->label(__('dashboard.started_at'))
                    ->dateTime('Y-m-d')
                    ->sortable(),

                TextColumn::make('trial_ends_at')
                    ->label(__('dashboard.trial_ends_at'))
                    ->dateTime('Y-m-d')
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label(__('dashboard.expires_at'))
                    ->dateTime('Y-m-d')
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('dashboard.status'))
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => __("dashboard.{$state}"))
                    ->color(fn (string $state): string => match ($state) {
                        'trial' => 'info',
                        'active' => 'success',
                        'expired' => 'danger',
                        'cancelled' => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label(__('dashboard.created_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('dashboard.status'))
                    ->options([
                        'trial' => __('dashboard.trial'),
                        'active' => __('dashboard.active'),
                        'expired' => __('dashboard.expired'),
                        'cancelled' => __('dashboard.cancelled'),
                    ])
                    ->native(false),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('dashboard.no_packages'));
    }
}
