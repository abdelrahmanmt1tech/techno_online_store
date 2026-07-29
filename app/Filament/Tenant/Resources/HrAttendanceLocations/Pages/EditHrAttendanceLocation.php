<?php

namespace App\Filament\Tenant\Resources\HrAttendanceLocations\Pages;

use App\Filament\Tenant\Resources\HrAttendanceLocations\HrAttendanceLocationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHrAttendanceLocation extends EditRecord
{
    protected static string $resource = HrAttendanceLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
