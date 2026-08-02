<?php

namespace App\Filament\Tenant\Resources\FinancialPeriods\Tables;

use App\Filament\Tenant\Resources\FinancialPeriods\FinancialPeriodResource;
use App\Models\Tenant\FinancialPeriod;
use App\Services\Accounting\CarryForwardPeriodService;
use App\Services\Accounting\CloseFinancialPeriodService;
use App\Services\Accounting\ReopenFinancialPeriodService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class FinancialPeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('dashboard.resources.financial_period.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label(__('dashboard.resources.financial_period.code'))
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('start_date')
                    ->label(__('dashboard.resources.financial_period.start_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label(__('dashboard.resources.financial_period.end_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('dashboard.resources.financial_period.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? (string) $state)
                    ->sortable(),
                IconColumn::make('is_current')
                    ->label(__('dashboard.resources.financial_period.is_current'))
                    ->boolean(),
                TextColumn::make('parentPeriod.name')
                    ->label(__('dashboard.resources.financial_period.parent_period'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('operations_count')
                    ->label(__('dashboard.resources.financial_period.operations_count'))
                    ->counts('operations')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('balances_count')
                    ->label(__('dashboard.resources.financial_period.balances_count'))
                    ->counts('balances')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('closed_at')
                    ->label(__('dashboard.resources.financial_period.closed_at'))
                    ->dateTime()
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('dashboard.resources.financial_period.status'))
                    ->options(\App\Enums\FinancialPeriodStatus::options())
                    ->native(false),
                SelectFilter::make('is_current')
                    ->label(__('dashboard.resources.financial_period.is_current'))
                    ->options([
                        '1' => __('dashboard.resources.financial_period.current_only'),
                        '0' => __('dashboard.resources.financial_period.non_current_only'),
                    ])
                    ->query(fn ($query, array $data) => $query->when(
                        $data['value'] !== null && $data['value'] !== '',
                        fn ($q) => $q->where('is_current', (bool) (int) $data['value'])
                    ))
                    ->native(false),
            ])
            ->recordActions([
                ViewAction::make()
                    ->authorize(fn (): bool => Auth::user()?->can('financial_periods.show') ?? false),
                EditAction::make()
                    ->authorize(fn (): bool => Auth::user()?->can('financial_periods.update') ?? false),

                Action::make('opening_entry')
                    ->label(__('dashboard.resources.financial_period.opening_entries'))
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (FinancialPeriod $record): string => FinancialPeriodResource::getUrl('opening-entries', ['record' => $record]))
                    ->authorize(fn (): bool => Auth::user()?->can('financial_periods.create_opening_entry') ?? false),

                Action::make('balances')
                    ->label(__('dashboard.resources.financial_period.view_balances'))
                    ->icon('heroicon-o-table-cells')
                    ->url(fn (FinancialPeriod $record): string => FinancialPeriodResource::getUrl('balances', ['record' => $record]))
                    ->authorize(fn (): bool => Auth::user()?->can('financial_periods.view_balances') ?? false),

                Action::make('close')
                    ->label(__('dashboard.resources.financial_period.close'))
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn (FinancialPeriod $record): bool => $record->canBeClosed())
                    ->authorize(fn (): bool => Auth::user()?->can('financial_periods.close') ?? false)
                    ->form([
                        Textarea::make('notes')
                            ->label(__('dashboard.resources.financial_period.notes')),
                    ])
                    ->requiresConfirmation()
                    ->action(function (FinancialPeriod $record, array $data): void {
                        app(CloseFinancialPeriodService::class)
                            ->handle($record, Auth::user(), $data['notes'] ?? null);

                        Notification::make()
                            ->success()
                            ->title(__('dashboard.resources.financial_period.close_success'))
                            ->send();
                    }),
                Action::make('carry_forward')
                    ->label(__('dashboard.resources.financial_period.carry_forward'))
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('warning')
                    ->visible(fn (FinancialPeriod $record): bool => $record->isClosed())
                    ->authorize(fn (): bool => Auth::user()?->can('financial_periods.carry_forward') ?? false)
                    ->form([
                        Select::make('to_period_id')
                            ->label(__('dashboard.resources.financial_period.target_period'))
                            ->options(fn (FinancialPeriod $record): array => FinancialPeriod::query()
                                ->whereKeyNot($record->id)
                                ->orderBy('start_date')
                                ->pluck('name', 'id')
                                ->toArray())
                            ->required()
                            ->native(false)
                            ->searchable(),
                        Textarea::make('notes')
                            ->label(__('dashboard.resources.financial_period.notes')),
                    ])
                    ->action(function (FinancialPeriod $record, array $data): void {
                        $toPeriod = FinancialPeriod::query()->findOrFail((int) $data['to_period_id']);
                        app(CarryForwardPeriodService::class)->handle($record, $toPeriod, Auth::user(), $data['notes'] ?? null);

                        Notification::make()
                            ->success()
                            ->title(__('dashboard.resources.financial_period.carry_forward_success'))
                            ->send();
                    }),
                Action::make('reopen')
                    ->label(__('dashboard.resources.financial_period.reopen'))
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->visible(fn (FinancialPeriod $record): bool => $record->canBeReopened())
                    ->authorize(fn (): bool => Auth::user()?->can('financial_periods.reopen') ?? false)
                    ->form([
                        Textarea::make('notes')
                            ->label(__('dashboard.resources.financial_period.notes'))
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->action(function (FinancialPeriod $record, array $data): void {
                        app(ReopenFinancialPeriodService::class)->handle($record, Auth::user(), $data['notes'] ?? null);

                        Notification::make()
                            ->success()
                            ->title(__('dashboard.resources.financial_period.reopen_success'))
                            ->send();
                    }),
                DeleteAction::make()
                    ->authorize(fn (): bool => Auth::user()?->can('financial_periods.delete') ?? false),
            ]);
    }
}
