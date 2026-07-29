<?php

namespace App\Filament\Tenant\Resources\HrEmployees\Pages;

use App\Filament\Tenant\Resources\HrEmployees\HrEmployeeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHrEmployees extends ListRecords
{
    protected static string $resource = HrEmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
