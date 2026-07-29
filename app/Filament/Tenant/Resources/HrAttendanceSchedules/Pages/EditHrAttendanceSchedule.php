<?php

namespace App\Filament\Tenant\Resources\HrAttendanceSchedules\Pages;

use App\Filament\Tenant\Resources\HrAttendanceSchedules\HrAttendanceScheduleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHrAttendanceSchedule extends EditRecord
{
    protected static string $resource = HrAttendanceScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
