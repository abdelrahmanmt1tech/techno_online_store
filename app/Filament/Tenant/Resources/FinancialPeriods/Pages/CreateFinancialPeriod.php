<?php

namespace App\Filament\Tenant\Resources\FinancialPeriods\Pages;

use App\Filament\Tenant\Resources\FinancialPeriods\FinancialPeriodResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFinancialPeriod extends CreateRecord
{
    protected static string $resource = FinancialPeriodResource::class;
}
