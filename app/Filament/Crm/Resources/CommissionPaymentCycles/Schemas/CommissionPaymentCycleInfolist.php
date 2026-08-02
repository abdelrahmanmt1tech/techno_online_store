<?php

namespace App\Filament\Crm\Resources\CommissionPaymentCycles\Schemas;

use App\Enums\Crm\CommissionPaymentCycleStatus;
use App\Enums\PaymentMethod;
use App\Models\Tenant\CommissionPaymentCycle;
use App\Services\Crm\Commission\CommissionCycleTotalsCalculator;
use App\Support\Crm\Commission\CommissionPaymentCycleAccess;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class CommissionPaymentCycleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('crm.payment_cycles.sections.details'))
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    TextEntry::make('cycle_number')
                        ->label(__('crm.payment_cycles.fields.cycle_number')),
                    TextEntry::make('period_from')
                        ->label(__('crm.payment_cycles.fields.period_from'))
                        ->date(),
                    TextEntry::make('period_to')
                        ->label(__('crm.payment_cycles.fields.period_to'))
                        ->date(),
                    TextEntry::make('status')
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
                    TextEntry::make('branch.name')
                        ->label(__('dashboard.fields.branch'))
                        ->placeholder('-'),
                    TextEntry::make('payment_date')
                        ->label(__('crm.payment_cycles.fields.payment_date'))
                        ->date()
                        ->placeholder('-'),
                    TextEntry::make('payment_method')
                        ->label(__('crm.payment_cycles.fields.payment_method'))
                        ->formatStateUsing(fn (?string $state): string => $state
                            ? PaymentMethod::from($state)->label()
                            : '-')
                        ->placeholder('-'),
                    TextEntry::make('reference_number')
                        ->label(__('crm.payment_cycles.fields.reference_number'))
                        ->placeholder('-'),
                    TextEntry::make('createdBy.name')
                        ->label(__('crm.fields.created_by'))
                        ->placeholder('-'),
                    TextEntry::make('approvedBy.name')
                        ->label(__('crm.payment_cycles.fields.approved_by'))
                        ->placeholder('-'),
                    TextEntry::make('paidBy.name')
                        ->label(__('crm.payment_cycles.fields.paid_by'))
                        ->placeholder('-'),
                    TextEntry::make('created_at')
                        ->label(__('crm.fields.created_at'))
                        ->dateTime(),
                    TextEntry::make('notes')
                        ->label(__('crm.fields.notes'))
                        ->columnSpanFull()
                        ->placeholder('-'),
                ]),
            Section::make(__('crm.payment_cycles.sections.financial_summary'))
                ->columnSpanFull()
                ->columns(3)
                ->visible(fn (CommissionPaymentCycle $record): bool => Auth::user() !== null
                    && CommissionPaymentCycleAccess::canViewFinancialTotals(Auth::user(), $record))
                ->schema([
                    TextEntry::make('planned_total')
                        ->label(__('crm.payment_cycles.fields.planned_total'))
                        ->state(fn (CommissionPaymentCycle $record): string => CommissionCycleTotalsCalculator::plannedTotal($record))
                        ->numeric(decimalPlaces: 2),
                    TextEntry::make('total_paid')
                        ->label(__('crm.payment_cycles.fields.total_paid'))
                        ->state(fn (CommissionPaymentCycle $record): string => CommissionCycleTotalsCalculator::forCycle($record)['total_paid'])
                        ->numeric(decimalPlaces: 2),
                    TextEntry::make('total_reversed')
                        ->label(__('crm.payment_cycles.fields.total_reversed'))
                        ->state(fn (CommissionPaymentCycle $record): string => CommissionCycleTotalsCalculator::forCycle($record)['total_reversed'])
                        ->numeric(decimalPlaces: 2),
                    TextEntry::make('net_paid')
                        ->label(__('crm.payment_cycles.fields.net_paid'))
                        ->state(fn (CommissionPaymentCycle $record): string => CommissionCycleTotalsCalculator::forCycle($record)['net_paid'])
                        ->numeric(decimalPlaces: 2),
                ]),
        ]);
    }
}
