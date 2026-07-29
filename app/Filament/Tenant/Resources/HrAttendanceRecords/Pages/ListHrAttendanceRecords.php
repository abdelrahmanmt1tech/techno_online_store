<?php

namespace App\Filament\Tenant\Resources\HrAttendanceRecords\Pages;

use App\Filament\Tenant\Resources\HrAttendanceRecords\HrAttendanceRecordResource;
use Filament\Resources\Pages\ListRecords;

class ListHrAttendanceRecords extends ListRecords
{
    protected static string $resource = HrAttendanceRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
