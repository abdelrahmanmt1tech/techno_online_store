<?php

namespace App\Filament\Resources\Tenants\RelationManagers;

use App\Models\Currency;
use App\Models\Package;
use Carbon\Carbon;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
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
                            ->options(fn() => Package::where('is_active', true)
                                ->orderBy('sort')
                                ->get()
                                ->mapWithKeys(fn($package) => [
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

                                $packagePrice = $package->prices
                                    ->firstWhere('country_id', $tenant?->country_id)
                                    ?? $package->prices->first();

                                $startedAt = $get('started_at') ? Carbon::parse($get('started_at')) : now();

                                if ($packagePrice) {
                                    $set('price', $packagePrice->price);
                                    $set('currency_id', $packagePrice->currency_id);
                                    $set('duration_type', $packagePrice->duration_type);
                                    $set('duration', $packagePrice->duration);
                                    $set('expires_at', $this->resolveExpiry(
                                        $startedAt,
                                        $packagePrice->duration,
                                        $packagePrice->duration_type,
                                    ));
                                }

                                $set('trial_ends_at', $package->trials_duration
                                    ? $startedAt->copy()->addDays($package->trials_duration)->toDateTimeString()
                                    : null);
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
                            ->options(fn() => Currency::where('is_active', true)
                                ->orderBy('sort_order')
                                ->get()
                                ->mapWithKeys(fn($currency) => [
                                    $currency->id => $currency->code . ' (' . $currency->getTranslation('name', app()->getLocale()) . ')',
                                ]))
                            ->searchable()
                            ->native(false)
                            ->columnSpan(1),

                        Select::make('duration_type')
                            ->label(__('dashboard.duration_type'))
                            ->options([
                                'day' => __('dashboard.day'),
                                'month' => __('dashboard.month'),
                                'year' => __('dashboard.year'),
                            ])
                            ->native(false)
                            ->default('month')
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn($state, $set, $get) => $this->syncExpiry($set, $get))
                            ->columnSpan(1),

                        TextInput::make('duration')
                            ->label(__('dashboard.duration'))
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn($state, $set, $get) => $this->syncExpiry($set, $get))
                            ->columnSpan(1),

                        DateTimePicker::make('started_at')
                            ->label(__('dashboard.started_at'))
                            ->default(now())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn($state, $set, $get) => $this->syncExpiry($set, $get))
                            ->columnSpan(1),

                        DateTimePicker::make('trial_ends_at')
                            ->label(__('dashboard.trial_ends_at'))
                            ->columnSpan(1),

                        DateTimePicker::make('expires_at')
                            ->label(__('dashboard.expires_at'))
                            ->columnSpan(1),
                    ])
                    ->columnSpanFull(),
            ]);
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
        $duration = $get('duration');
        $durationType = $get('duration_type');

        if (! $startedAt || ! $duration || ! $durationType) {
            return;
        }

        $set('expires_at', $this->resolveExpiry(
            Carbon::parse($startedAt),
            (int) $duration,
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
                    ->money(fn($record) => $record->currency?->code ?? 'SAR')
                    ->sortable(),

                TextColumn::make('duration')
                    ->label(__('dashboard.duration'))
                    ->formatStateUsing(fn($state, $record) => $state . ' ' . __("dashboard.{$record->duration_type}"))
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
                    ->formatStateUsing(fn(string $state): string => __("dashboard.{$state}"))
                    ->color(fn(string $state): string => match ($state) {
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
