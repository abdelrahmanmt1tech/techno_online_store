<?php

namespace App\Filament\Tenant\Resources\HrPayrollPeriods\Pages;

use App\Filament\Tenant\Resources\HrPayrollPeriods\HrPayrollPeriodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHrPayrollPeriods extends ListRecords
{
    protected static string $resource = HrPayrollPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
