<?php

namespace App\Filament\Tenant\Resources\FinancialPeriods\Pages;

use App\Filament\Tenant\Resources\FinancialPeriods\FinancialPeriodResource;
use Filament\Resources\Pages\EditRecord;

class EditFinancialPeriod extends EditRecord
{
    protected static string $resource = FinancialPeriodResource::class;
}
