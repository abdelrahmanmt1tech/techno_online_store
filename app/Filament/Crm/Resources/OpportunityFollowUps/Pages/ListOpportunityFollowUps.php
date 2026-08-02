<?php

namespace App\Filament\Crm\Resources\OpportunityFollowUps\Pages;

use App\Filament\Crm\Resources\OpportunityFollowUps\OpportunityFollowUpResource;
use App\Filament\Crm\Widgets\FollowUpStatsOverview;
use App\Models\Tenant\OpportunityFollowUp;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListOpportunityFollowUps extends ListRecords
{
    protected static string $resource = OpportunityFollowUpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FollowUpStatsOverview::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->label(__('crm.tabs.all')),
            'upcoming' => Tab::make()
                ->label(__('crm.tabs.upcoming'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->whereNull('completed_at')
                    ->where('scheduled_at', '>=', now()))
                ->badge(fn (): int => OpportunityFollowUp::query()
                    ->whereNull('completed_at')
                    ->where('scheduled_at', '>=', now())
                    ->count()),
            'overdue' => Tab::make()
                ->label(__('crm.tabs.overdue'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->overdue())
                ->badge(fn (): int => OpportunityFollowUp::query()->overdue()->count())
                ->badgeColor('danger'),
            'completed' => Tab::make()
                ->label(__('crm.tabs.completed'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNotNull('completed_at')),
            'mine' => Tab::make()
                ->label(__('crm.tabs.mine'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->whereNull('completed_at')
                    ->where('assigned_to', Auth::id())),
        ];
    }
}
