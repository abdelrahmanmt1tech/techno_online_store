<?php

namespace App\Filament\Crm\Resources\CommissionPaymentCycles\Pages;

use App\Filament\Crm\Resources\CommissionPaymentCycles\Actions\CommissionPaymentCycleActions;
use App\Filament\Crm\Resources\CommissionPaymentCycles\CommissionPaymentCycleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCommissionPaymentCycle extends ViewRecord
{
    protected static string $resource = CommissionPaymentCycleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...CommissionPaymentCycleActions::headerActions($this->record),
            EditAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
