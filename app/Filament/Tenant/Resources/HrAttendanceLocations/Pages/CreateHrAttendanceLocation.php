<?php

namespace App\Filament\Tenant\Resources\HrAttendanceLocations\Pages;

use App\Filament\Tenant\Resources\HrAttendanceLocations\HrAttendanceLocationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHrAttendanceLocation extends CreateRecord
{
    protected static string $resource = HrAttendanceLocationResource::class;
}
