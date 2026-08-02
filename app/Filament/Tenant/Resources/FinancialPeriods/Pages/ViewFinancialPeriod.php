<?php

namespace App\Filament\Tenant\Resources\FinancialPeriods\Pages;

use App\Filament\Tenant\Resources\FinancialPeriods\FinancialPeriodResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewFinancialPeriod extends ViewRecord
{
    protected static string $resource = FinancialPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->authorize(fn (): bool => Auth::user()?->can('financial_periods.update') ?? false),
            Action::make('opening_entry')
                ->label(__('dashboard.resources.financial_period.opening_entries'))
                ->url(fn (): string => FinancialPeriodResource::getUrl('opening-entries', ['record' => $this->record]))
                ->authorize(fn (): bool => Auth::user()?->can('financial_periods.create_opening_entry') ?? false),
            Action::make('balances')
                ->label(__('dashboard.resources.financial_period.view_balances'))
                ->url(fn (): string => FinancialPeriodResource::getUrl('balances', ['record' => $this->record]))
                ->authorize(fn (): bool => Auth::user()?->can('financial_periods.view_balances') ?? false),
        ];
    }
}
