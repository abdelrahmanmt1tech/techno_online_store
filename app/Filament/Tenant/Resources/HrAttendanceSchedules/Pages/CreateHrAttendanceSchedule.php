<?php

namespace App\Filament\Tenant\Resources\HrAttendanceSchedules\Pages;

use App\Filament\Tenant\Resources\HrAttendanceSchedules\HrAttendanceScheduleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHrAttendanceSchedule extends CreateRecord
{
    protected static string $resource = HrAttendanceScheduleResource::class;
}
