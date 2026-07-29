<?php

namespace App\Filament\Tenant\Resources\HrDepartments\Pages;

use App\Filament\Tenant\Resources\HrDepartments\HrDepartmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHrDepartment extends EditRecord
{
    protected static string $resource = HrDepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
