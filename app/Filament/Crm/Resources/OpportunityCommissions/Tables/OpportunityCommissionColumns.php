<?php

namespace App\Filament\Crm\Resources\OpportunityCommissions\Tables;

use App\Enums\Crm\CommissionStatus;
use App\Enums\Crm\CommissionType;
use Filament\Tables\Columns\TextColumn;

final class OpportunityCommissionColumns
{
    /**
     * @return array<int, TextColumn>
     */
    public static function make(): array
    {
        return [
            TextColumn::make('user.name')
                ->label(__('crm.commissions.fields.employee'))
                ->searchable()
                ->sortable(),
            TextColumn::make('commission_type')
                ->label(__('crm.commissions.fields.commission_type'))
                ->badge()
                ->formatStateUsing(fn (CommissionType $state): string => $state->label()),
            TextColumn::make('base_amount')
                ->label(__('crm.commissions.fields.base_amount'))
                ->numeric(decimalPlaces: 2)
                ->sortable(),
            TextColumn::make('commission_percentage')
                ->label(__('crm.commissions.fields.commission_percentage'))
                ->numeric(decimalPlaces: 2)
                ->suffix('%'),
            TextColumn::make('commission_amount')
                ->label(__('crm.commissions.fields.commission_amount'))
                ->numeric(decimalPlaces: 2)
                ->sortable(),
            TextColumn::make('paid_amount')
                ->label(__('crm.commissions.fields.paid_amount'))
                ->numeric(decimalPlaces: 2),
            TextColumn::make('remaining_amount')
                ->label(__('crm.commissions.fields.remaining_amount'))
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
                ->label(__('crm.commissions.fields.approved_at'))
                ->dateTime()
                ->placeholder('-'),
            TextColumn::make('due_at')
                ->label(__('crm.commissions.fields.due_at'))
                ->date()
                ->placeholder('-'),
        ];
    }
}
