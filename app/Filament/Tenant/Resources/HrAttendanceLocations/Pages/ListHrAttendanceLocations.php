<?php

namespace App\Filament\Tenant\Resources\HrAttendanceLocations\Pages;

use App\Filament\Tenant\Resources\HrAttendanceLocations\HrAttendanceLocationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHrAttendanceLocations extends ListRecords
{
    protected static string $resource = HrAttendanceLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
