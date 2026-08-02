<?php

namespace App\Filament\Crm\Resources\CommissionPaymentCycles\Pages;

use App\Filament\Crm\Resources\CommissionPaymentCycles\CommissionPaymentCycleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCommissionPaymentCycles extends ListRecords
{
    protected static string $resource = CommissionPaymentCycleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('crm.payment_cycles.actions.create')),
        ];
    }
}
