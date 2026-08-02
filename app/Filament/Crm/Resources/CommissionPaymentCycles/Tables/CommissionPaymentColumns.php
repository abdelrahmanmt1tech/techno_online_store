<?php

namespace App\Filament\Crm\Resources\CommissionPaymentCycles\Tables;

use App\Enums\Crm\CommissionPaymentEntryType;
use App\Enums\PaymentMethod;
use Filament\Tables\Columns\TextColumn;

final class CommissionPaymentColumns
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
            TextColumn::make('opportunityCommission.opportunity.title')
                ->label(__('crm.fields.opportunity'))
                ->limit(40)
                ->placeholder('-'),
            TextColumn::make('entry_type')
                ->label(__('crm.payment_cycles.fields.entry_type'))
                ->badge()
                ->formatStateUsing(fn (CommissionPaymentEntryType $state): string => $state->label())
                ->color(fn (CommissionPaymentEntryType $state): string => match ($state) {
                    CommissionPaymentEntryType::PAYMENT => 'success',
                    CommissionPaymentEntryType::REVERSAL => 'danger',
                }),
            TextColumn::make('amount')
                ->label(__('crm.payment_cycles.fields.amount'))
                ->numeric(decimalPlaces: 2)
                ->sortable(),
            TextColumn::make('payment_method')
                ->label(__('crm.payment_cycles.fields.payment_method'))
                ->formatStateUsing(fn (?string $state): string => $state
                    ? PaymentMethod::from($state)->label()
                    : '-')
                ->placeholder('-')
                ->toggleable(),
            TextColumn::make('reference_number')
                ->label(__('crm.payment_cycles.fields.reference_number'))
                ->placeholder('-')
                ->toggleable(),
            TextColumn::make('executed_at')
                ->label(__('crm.payment_cycles.fields.executed_at'))
                ->dateTime()
                ->sortable(),
            TextColumn::make('executedBy.name')
                ->label(__('crm.payment_cycles.fields.executed_by'))
                ->placeholder('-'),
            TextColumn::make('reversal_reason')
                ->label(__('crm.payment_cycles.fields.reversal_reason'))
                ->limit(40)
                ->placeholder('-')
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }
}
