<?php

namespace App\Filament\Tenant\Resources\HrEmployees\Pages;

use App\Filament\Tenant\Resources\HrEmployees\HrEmployeeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHrEmployee extends EditRecord
{
    protected static string $resource = HrEmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
