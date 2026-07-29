<?php

namespace App\Filament\Tenant\Resources\HrAttendanceRecords\Pages;

use App\Actions\Hr\AdjustAttendanceAction;
use App\Filament\Tenant\Resources\HrAttendanceRecords\HrAttendanceRecordResource;
use App\Models\Tenant\HrAttendanceRecord;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditHrAttendanceRecord extends EditRecord
{
    protected static string $resource = HrAttendanceRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var HrAttendanceRecord $record */
        $note = $data['admin_note'] ?? null;

        return app(AdjustAttendanceAction::class)->execute($record, $data, $note);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('hr.notifications.attendance_adjusted'));
    }
}
