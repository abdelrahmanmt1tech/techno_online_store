<?php

namespace App\Filament\Tenant\Resources\HrPayrollPeriods\Pages;

use App\Filament\Tenant\Resources\HrPayrollPeriods\HrPayrollPeriodResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHrPayrollPeriod extends EditRecord
{
    protected static string $resource = HrPayrollPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
