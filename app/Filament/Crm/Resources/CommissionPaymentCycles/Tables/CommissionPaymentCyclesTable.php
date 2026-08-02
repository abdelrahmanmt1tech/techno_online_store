<?php

namespace App\Filament\Crm\Resources\CommissionPaymentCycles\Tables;

use App\Enums\Crm\CommissionPaymentCycleStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CommissionPaymentCyclesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cycle_number')
                    ->label(__('crm.payment_cycles.fields.cycle_number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('period_from')
                    ->label(__('crm.payment_cycles.fields.period_from'))
                    ->date()
                    ->sortable(),
                TextColumn::make('period_to')
                    ->label(__('crm.payment_cycles.fields.period_to'))
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('crm.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (CommissionPaymentCycleStatus $state): string => $state->label())
                    ->color(fn (CommissionPaymentCycleStatus $state): string => match ($state) {
                        CommissionPaymentCycleStatus::DRAFT => 'gray',
                        CommissionPaymentCycleStatus::PENDING_APPROVAL => 'warning',
                        CommissionPaymentCycleStatus::APPROVED => 'info',
                        CommissionPaymentCycleStatus::PARTIALLY_PAID => 'warning',
                        CommissionPaymentCycleStatus::PAID => 'success',
                        CommissionPaymentCycleStatus::CANCELLED => 'gray',
                    }),
                TextColumn::make('branch.name')
                    ->label(__('dashboard.fields.branch'))
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('crm.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('crm.fields.status'))
                    ->options(CommissionPaymentCycleStatus::options()),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('crm.payment_cycles.empty'));
    }
}
