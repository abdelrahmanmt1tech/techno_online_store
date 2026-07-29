<?php

namespace App\Filament\Tenant\Resources\HrPayrollPeriods\Pages;

use App\Filament\Tenant\Resources\HrPayrollPeriods\HrPayrollPeriodResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHrPayrollPeriod extends CreateRecord
{
    protected static string $resource = HrPayrollPeriodResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
