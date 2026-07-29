<?php

namespace App\Filament\Tenant\Resources\HrEmployees\Pages;

use App\Filament\Tenant\Resources\HrEmployees\HrEmployeeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHrEmployee extends CreateRecord
{
    protected static string $resource = HrEmployeeResource::class;
}
