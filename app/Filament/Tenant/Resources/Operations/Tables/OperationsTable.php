<?php

namespace App\Filament\Tenant\Resources\Operations\Tables;

use App\Enums\OperationType;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Client;
use App\Models\Tenant\FinancialPeriod;
use App\Models\Tenant\Operation;
use App\Models\Tenant\Supplier;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Simplified operations table for techno — Ticket/Reservation/Payment/Franchise stripped.
 */
class OperationsTable
{
    public static function configure(Table $table, ?callable $priorDebitCreditResolver = null): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('erp.resources.operation', [], 'erp') !== 'erp.resources.operation' ? __('erp.resources.operation') : 'Operation #')->sortable(),
                TextColumn::make('date')->dateTime()->sortable(),
                TextColumn::make('reference_no')->searchable()->toggleable(),
                TextColumn::make('comment')->limit(40)->wrap()->toggleable(),
                TextColumn::make('operation_type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof OperationType ? $state->value : $state),
                TextColumn::make('total_debit')->numeric(2)->sortable(),
                TextColumn::make('total_credit')->numeric(2)->sortable(),
                IconColumn::make('is_posted')->boolean()->toggleable(),
                IconColumn::make('is_locked')->boolean()->toggleable(),
                TextColumn::make('financialPeriod.name')->toggleable(),
                TextColumn::make('created_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('operation_type')
                    ->options(collect(OperationType::cases())->mapWithKeys(
                        fn (OperationType $t) => [$t->value => $t->name]
                    )->all()),
                SelectFilter::make('financial_period_id')
                    ->options(fn (): array => FinancialPeriod::query()->pluck('name', 'id')->all())
                    ->searchable(),
                Filter::make('date_range')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('date', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('date', '<=', $date));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }
}
