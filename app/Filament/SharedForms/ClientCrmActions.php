<?php

namespace App\Filament\SharedForms;

use App\Filament\Crm\Resources\Opportunities\OpportunityResource;
use App\Filament\Tenant\Resources\Clients\ClientResource;
use App\Models\Tenant\Client;
use App\Models\Tenant\Opportunity;
use App\Models\Tenant\OpportunityFollowUp;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

/**
 * CRM client helpers without Meta/WhatsApp integrations.
 */
class ClientCrmActions
{
    public static function viewWorkspaceAction(?string $name = 'view'): Action
    {
        return Action::make($name)
            ->label(__('crm.actions.view_client'))
            ->icon(Heroicon::Eye)
            ->color('gray')
            ->url(fn (Client $record): string => ClientResource::getUrl('view', ['record' => $record]));
    }

    /**
     * @return array<int, Action>
     */
    public static function clientPageActions(): array
    {
        return [];
    }

    public static function openOpportunitiesUrl(Client $record): string
    {
        return OpportunityResource::getUrl('index', [
            'tableFilters' => [
                'client_id' => ['value' => $record->getKey()],
            ],
        ]);
    }

    public static function opportunityViewUrl(Opportunity $opportunity): string
    {
        return OpportunityResource::getUrl('view', ['record' => $opportunity]);
    }

    public static function formatFollowUpSummary(?OpportunityFollowUp $followUp): string
    {
        if (! $followUp) {
            return '—';
        }

        $when = $followUp->scheduled_at?->format('Y-m-d H:i')
            ?? $followUp->completed_at?->format('Y-m-d H:i')
            ?? '—';

        return trim(($followUp->title ?? '').' · '.$when);
    }
}
