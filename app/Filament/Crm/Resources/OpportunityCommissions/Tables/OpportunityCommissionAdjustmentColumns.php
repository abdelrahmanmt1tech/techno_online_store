<?php

namespace App\Filament\Crm\Resources\OpportunityCommissions\Tables;

use App\Enums\Crm\CommissionAdjustmentDirection;
use App\Enums\Crm\CommissionAdjustmentStatus;
use Filament\Tables\Columns\TextColumn;

final class OpportunityCommissionAdjustmentColumns
{
    /**
     * @return array<int, TextColumn>
     */
    public static function make(): array
    {
        return [
            TextColumn::make('direction')
                ->label(__('crm.commissions.adjustments.fields.direction'))
                ->badge()
                ->formatStateUsing(fn (CommissionAdjustmentDirection $state): string => $state->label())
                ->color(fn (CommissionAdjustmentDirection $state): string => match ($state) {
                    CommissionAdjustmentDirection::INCREASE => 'success',
                    CommissionAdjustmentDirection::DECREASE => 'danger',
                }),
            TextColumn::make('amount')
                ->label(__('crm.commissions.adjustments.fields.amount'))
                ->numeric(decimalPlaces: 2)
                ->sortable(),
            TextColumn::make('status')
                ->label(__('crm.fields.status'))
                ->badge()
                ->formatStateUsing(fn (CommissionAdjustmentStatus $state): string => $state->label())
                ->color(fn (CommissionAdjustmentStatus $state): string => match ($state) {
                    CommissionAdjustmentStatus::PENDING => 'warning',
                    CommissionAdjustmentStatus::APPROVED => 'success',
                    CommissionAdjustmentStatus::REJECTED => 'danger',
                    CommissionAdjustmentStatus::CANCELLED => 'gray',
                }),
            TextColumn::make('reason')
                ->label(__('crm.commissions.adjustments.fields.reason'))
                ->limit(60)
                ->tooltip(fn (?string $state): ?string => $state),
            TextColumn::make('balance_before')
                ->label(__('crm.commissions.adjustments.fields.balance_before'))
                ->numeric(decimalPlaces: 2),
            TextColumn::make('balance_after')
                ->label(__('crm.commissions.adjustments.fields.balance_after'))
                ->numeric(decimalPlaces: 2),
            TextColumn::make('createdBy.name')
                ->label(__('crm.fields.created_by'))
                ->placeholder('-'),
            TextColumn::make('approvedBy.name')
                ->label(__('crm.commissions.adjustments.fields.approved_by'))
                ->placeholder('-'),
            TextColumn::make('rejectedBy.name')
                ->label(__('crm.commissions.adjustments.fields.rejected_by'))
                ->placeholder('-'),
            TextColumn::make('created_at')
                ->label(__('crm.fields.created_at'))
                ->dateTime()
                ->sortable(),
            TextColumn::make('approved_at')
                ->label(__('crm.commissions.adjustments.fields.approved_at'))
                ->dateTime()
                ->placeholder('-'),
            TextColumn::make('rejected_at')
                ->label(__('crm.commissions.adjustments.fields.rejected_at'))
                ->dateTime()
                ->placeholder('-'),
            TextColumn::make('rejection_reason')
                ->label(__('crm.commissions.adjustments.fields.rejection_reason'))
                ->limit(40)
                ->placeholder('-')
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }
}
