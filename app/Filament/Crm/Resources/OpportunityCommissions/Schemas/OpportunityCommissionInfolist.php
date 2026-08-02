<?php

namespace App\Filament\Crm\Resources\OpportunityCommissions\Schemas;

use App\Enums\Crm\CommissionStatus;
use App\Enums\Crm\CommissionType;
use App\Models\Tenant\OpportunityCommission;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OpportunityCommissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('crm.commissions.sections.details'))
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    TextEntry::make('opportunity.title')
                        ->label(__('crm.fields.opportunity')),
                    TextEntry::make('user.name')
                        ->label(__('crm.commissions.fields.employee')),
                    TextEntry::make('commission_type')
                        ->label(__('crm.commissions.fields.commission_type'))
                        ->formatStateUsing(fn (CommissionType $state): string => $state->label()),
                    TextEntry::make('base_amount')
                        ->label(__('crm.commissions.fields.base_amount'))
                        ->numeric(decimalPlaces: 2),
                    TextEntry::make('commission_percentage')
                        ->label(__('crm.commissions.fields.commission_percentage'))
                        ->suffix('%'),
                    TextEntry::make('status')
                        ->label(__('crm.fields.status'))
                        ->badge()
                        ->formatStateUsing(fn (CommissionStatus $state): string => $state->label()),
                    TextEntry::make('approved_at')
                        ->label(__('crm.commissions.fields.approved_at'))
                        ->dateTime()
                        ->placeholder('-'),
                    TextEntry::make('approvedBy.name')
                        ->label(__('crm.commissions.fields.approved_by'))
                        ->placeholder('-'),
                    TextEntry::make('due_at')
                        ->label(__('crm.commissions.fields.due_at'))
                        ->date()
                        ->placeholder('-'),
                    TextEntry::make('notes')
                        ->label(__('crm.fields.notes'))
                        ->columnSpanFull()
                        ->placeholder('-'),
                ]),
            Section::make(__('crm.commissions.sections.financial_summary'))
                ->columnSpanFull()
                ->columns(3)
                ->visible(fn (OpportunityCommission $record): bool => auth()->user()?->can('crm_commissions.view_adjustments') ?? false)
                ->schema([
                    TextEntry::make('commission_amount')
                        ->label(__('crm.commissions.adjustments.fields.original_amount'))
                        ->numeric(decimalPlaces: 2),
                    TextEntry::make('approved_increase_adjustments_total')
                        ->label(__('crm.commissions.adjustments.fields.approved_increase_total'))
                        ->state(fn (OpportunityCommission $record): string => $record->approvedIncreaseAdjustmentsTotal())
                        ->numeric(decimalPlaces: 2),
                    TextEntry::make('approved_decrease_adjustments_total')
                        ->label(__('crm.commissions.adjustments.fields.approved_decrease_total'))
                        ->state(fn (OpportunityCommission $record): string => $record->approvedDecreaseAdjustmentsTotal())
                        ->numeric(decimalPlaces: 2),
                    TextEntry::make('effective_commission_amount')
                        ->label(__('crm.commissions.adjustments.fields.effective_amount'))
                        ->state(fn (OpportunityCommission $record): string => $record->effectiveCommissionAmount())
                        ->numeric(decimalPlaces: 2),
                    TextEntry::make('net_paid_amount')
                        ->label(__('crm.commissions.adjustments.fields.net_paid_amount'))
                        ->state(fn (OpportunityCommission $record): string => $record->netPaidAmount())
                        ->numeric(decimalPlaces: 2),
                    TextEntry::make('remaining_amount')
                        ->label(__('crm.commissions.adjustments.fields.remaining_amount'))
                        ->numeric(decimalPlaces: 2),
                ]),
            Section::make(__('crm.commissions.sections.audit_log'))
                ->columnSpanFull()
                ->schema([
                    RepeatableEntry::make('auditLogs')
                        ->label('')
                        ->schema([
                            TextEntry::make('action')
                                ->label(__('crm.commissions.fields.audit_action'))
                                ->formatStateUsing(fn (string $state): string => __("crm.commissions.audit_actions.{$state}")),
                            TextEntry::make('user.name')
                                ->label(__('crm.fields.created_by')),
                            TextEntry::make('amount_before')
                                ->label(__('crm.commissions.fields.amount_before'))
                                ->placeholder('-'),
                            TextEntry::make('amount_after')
                                ->label(__('crm.commissions.fields.amount_after'))
                                ->placeholder('-'),
                            TextEntry::make('created_at')
                                ->label(__('crm.fields.created_at'))
                                ->dateTime(),
                        ])
                        ->columns(5),
                ]),
        ]);
    }
}
