<?php

namespace App\Filament\Crm\Pages\MyCommissions\Tables;

use App\Enums\Crm\CommissionStatus;
use App\Enums\Crm\CommissionType;
use App\Models\Tenant\OpportunityCommission;
use Filament\Tables\Columns\TextColumn;

final class MyCommissionColumns
{
    /**
     * @return array<int, TextColumn>
     */
    public static function make(): array
    {
        return [
            TextColumn::make('opportunity.id')
                ->label(__('crm.own_commissions.fields.opportunity_number'))
                ->sortable(),
            TextColumn::make('opportunity.title')
                ->label(__('crm.fields.opportunity'))
                ->searchable()
                ->limit(40),
            TextColumn::make('opportunity.client.name')
                ->label(__('crm.fields.client'))
                ->formatStateUsing(fn ($state): string => is_array($state)
                    ? ($state[app()->getLocale()] ?? reset($state) ?: '-')
                    : (string) ($state ?? '-'))
                ->searchable(),
            TextColumn::make('branch.name')
                ->label(__('crm.fields.branch'))
                ->formatStateUsing(fn ($state): string => is_array($state)
                    ? ($state[app()->getLocale()] ?? reset($state) ?: '-')
                    : (string) ($state ?? '-'))
                ->placeholder('-'),
            TextColumn::make('commission_type')
                ->label(__('crm.commissions.fields.commission_type'))
                ->badge()
                ->formatStateUsing(fn (CommissionType $state): string => $state->label()),
            TextColumn::make('base_amount')
                ->label(__('crm.commissions.fields.base_amount'))
                ->numeric(decimalPlaces: 2),
            TextColumn::make('commission_percentage')
                ->label(__('crm.commissions.fields.commission_percentage'))
                ->numeric(decimalPlaces: 2)
                ->suffix('%'),
            TextColumn::make('commission_amount')
                ->label(__('crm.own_commissions.fields.original_amount'))
                ->numeric(decimalPlaces: 2),
            TextColumn::make('approved_increase_adjustments_total')
                ->label(__('crm.own_commissions.fields.increase_adjustments'))
                ->state(fn (OpportunityCommission $record): string => $record->approvedIncreaseAdjustmentsTotal())
                ->numeric(decimalPlaces: 2),
            TextColumn::make('approved_decrease_adjustments_total')
                ->label(__('crm.own_commissions.fields.decrease_adjustments'))
                ->state(fn (OpportunityCommission $record): string => $record->approvedDecreaseAdjustmentsTotal())
                ->numeric(decimalPlaces: 2),
            TextColumn::make('effective_amount')
                ->label(__('crm.own_commissions.fields.effective_amount'))
                ->state(fn (OpportunityCommission $record): string => $record->effectiveCommissionAmount())
                ->numeric(decimalPlaces: 2),
            TextColumn::make('net_paid')
                ->label(__('crm.own_commissions.fields.net_paid'))
                ->state(fn (OpportunityCommission $record): string => $record->netPaidAmount())
                ->numeric(decimalPlaces: 2),
            TextColumn::make('remaining_amount')
                ->label(__('crm.own_commissions.fields.remaining'))
                ->numeric(decimalPlaces: 2),
            TextColumn::make('status')
                ->label(__('crm.fields.status'))
                ->badge()
                ->formatStateUsing(fn (CommissionStatus $state): string => $state->label())
                ->color(fn (CommissionStatus $state): string => match ($state) {
                    CommissionStatus::DRAFT => 'gray',
                    CommissionStatus::PENDING => 'warning',
                    CommissionStatus::APPROVED => 'success',
                    CommissionStatus::PARTIALLY_PAID => 'info',
                    CommissionStatus::PAID => 'success',
                    CommissionStatus::REJECTED => 'danger',
                    CommissionStatus::CANCELLED => 'gray',
                }),
            TextColumn::make('approved_at')
                ->label(__('crm.own_commissions.fields.approved_at'))
                ->dateTime()
                ->placeholder('-'),
            TextColumn::make('due_at')
                ->label(__('crm.own_commissions.fields.due_at'))
                ->date()
                ->placeholder('-'),
        ];
    }
}
