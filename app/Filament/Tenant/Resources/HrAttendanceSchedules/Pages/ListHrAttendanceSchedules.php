<?php

namespace App\Filament\Tenant\Resources\HrAttendanceSchedules\Pages;

use App\Filament\Tenant\Resources\HrAttendanceSchedules\HrAttendanceScheduleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHrAttendanceSchedules extends ListRecords
{
    protected static string $resource = HrAttendanceScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
