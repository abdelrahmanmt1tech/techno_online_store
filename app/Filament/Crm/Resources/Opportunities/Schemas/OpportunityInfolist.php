<?php

namespace App\Filament\Crm\Resources\Opportunities\Schemas;

use App\Enums\Crm\OpportunityStageAction;
use App\Models\Tenant\Opportunity;
use App\Services\Crm\OpportunityTimelineService;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OpportunityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Callout::make(fn (Opportunity $record): string => self::calloutTitle($record))
                    ->description(fn (Opportunity $record): ?string => self::calloutDescription($record))
                    ->color(fn (Opportunity $record): string => self::calloutColor($record))
                    ->icon(fn (Opportunity $record): string => self::calloutIcon($record))
                    ->columnSpanFull()
                    ->visible(fn (Opportunity $record): bool => self::calloutVisible($record)),

             /*   ViewEntry::make('timeline')
                    ->label(__('crm.timeline.title'))
                    ->view('filament.crm.opportunity-timeline')
                    ->viewData(fn (Opportunity $record): array => [
                        'events' => app(OpportunityTimelineService::class)->build($record),
                    ])
                    ->columnSpanFull(),*/

                Section::make(__('crm.sections.details'))
                    ->columnSpanFull()
                    ->columns(4)
                    ->schema([
                        TextEntry::make('client.name')
                            ->label(__('crm.fields.client')),
                        TextEntry::make('createdBy.name')
                            ->label(__('crm.fields.created_by')),
                        TextEntry::make('opportunityStage.name')
                            ->label(__('crm.fields.stage'))
                            ->badge()
                            ->color(fn (Opportunity $record): string => $record->opportunityStage?->color ?? 'gray'),
                        TextEntry::make('title')
                            ->label(__('crm.fields.title')),
                        TextEntry::make('amount')
                            ->label(__('crm.fields.amount'))
                            ->money('SAR')
                            ->placeholder('-'),
                        TextEntry::make('agreed_amount')
                            ->label(__('crm.fields.agreed_amount'))
                            ->money('SAR')
                            ->placeholder('-'),
                        TextEntry::make('description')
                            ->label(__('crm.fields.description'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                        IconEntry::make('is_closed')
                            ->label(__('crm.fields.is_closed'))
                            ->boolean(),
                        TextEntry::make('closed_at')
                            ->label(__('crm.fields.closed_at'))
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('assignedTo.name')
                            ->label(__('crm.fields.assigned_to'))
                            ->placeholder('-'),
                        TextEntry::make('firstAssignedTo.name')
                            ->label(__('crm.fields.first_assigned_to'))
                            ->placeholder('-'),
                        TextEntry::make('campaign.name')
                            ->label(__('crm.fields.campaign'))
                            ->placeholder('-'),
                        TextEntry::make('branch.name')
                            ->label(__('dashboard.fields.branch'))
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->label(__('crm.fields.created_at'))
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label(__('crm.fields.updated_at'))
                            ->dateTime(),
                        KeyValueEntry::make('meta')
                            ->label(__('crm.fields.meta'))
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    protected static function calloutVisible(Opportunity $record): bool
    {
        $action = $record->opportunityStage?->action;

        if (! $action) {
            return $record->is_closed;
        }

        return in_array($action, [
            OpportunityStageAction::SUCCESS_CLOSE,
            OpportunityStageAction::FAILED_CLOSE,
            OpportunityStageAction::REOPEN,
        ], true) || $record->is_closed;
    }

    protected static function calloutTitle(Opportunity $record): string
    {
        $action = $record->opportunityStage?->action;

        return match ($action) {
            OpportunityStageAction::SUCCESS_CLOSE => __('crm.callouts.won_title'),
            OpportunityStageAction::FAILED_CLOSE => __('crm.callouts.lost_title'),
            OpportunityStageAction::REOPEN => __('crm.callouts.reopened_title'),
            default => $record->is_closed
                ? __('crm.callouts.closed_title')
                : __('crm.callouts.open_title'),
        };
    }

    protected static function calloutDescription(Opportunity $record): ?string
    {
        if ($record->closed_at) {
            return __('crm.callouts.closed_at',
                ['date' =>
                    $record->closed_at->format('Y-m-d H:i')]);
        }

        return null;
    }

    protected static function calloutColor(Opportunity $record): string
    {
        $action = $record->opportunityStage?->action;

        return match ($action) {
            OpportunityStageAction::SUCCESS_CLOSE => 'success',
            OpportunityStageAction::FAILED_CLOSE => 'danger',
            OpportunityStageAction::REOPEN => 'warning',
            default => $record->is_closed ? 'gray' : 'info',
        };
    }

    protected static function calloutIcon(Opportunity $record): string
    {
        $action = $record->opportunityStage?->action;

        return match ($action) {
            OpportunityStageAction::SUCCESS_CLOSE => 'heroicon-o-check-circle',
            OpportunityStageAction::FAILED_CLOSE => 'heroicon-o-x-circle',
            default => 'heroicon-o-information-circle',
        };
    }
}
