<?php

namespace App\Filament\Tenant\Resources\HrDepartments\Pages;

use App\Filament\Tenant\Resources\HrDepartments\HrDepartmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHrDepartments extends ListRecords
{
    protected static string $resource = HrDepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
