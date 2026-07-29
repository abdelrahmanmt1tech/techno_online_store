<?php

namespace App\Actions\Hr;

use App\Enums\Hr\AttendanceSource;
use App\Enums\Hr\AttendanceStatus;
use App\Models\Tenant\HrAttendanceRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AdjustAttendanceAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(HrAttendanceRecord $record, array $data, ?string $note = null): HrAttendanceRecord
    {
        return DB::connection('tenant')->transaction(function () use ($record, $data, $note) {
            /** @var HrAttendanceRecord $locked */
            $locked = HrAttendanceRecord::query()->whereKey($record->id)->lockForUpdate()->firstOrFail();

            $previous = $locked->only([
                'check_in_at',
                'check_out_at',
                'status',
                'late_minutes',
                'early_leave_minutes',
                'worked_minutes',
            ]);

            if (array_key_exists('check_in_at', $data)) {
                $locked->check_in_at = $data['check_in_at'];
            }
            if (array_key_exists('check_out_at', $data)) {
                $locked->check_out_at = $data['check_out_at'];
            }
            if (array_key_exists('status', $data)) {
                $status = $data['status'] instanceof AttendanceStatus
                    ? $data['status']
                    : AttendanceStatus::from((string) $data['status']);
                $locked->status = $status;
            }
            if (array_key_exists('late_minutes', $data)) {
                $locked->late_minutes = (int) $data['late_minutes'];
            }
            if (array_key_exists('early_leave_minutes', $data)) {
                $locked->early_leave_minutes = (int) $data['early_leave_minutes'];
            }

            if ($locked->check_in_at && $locked->check_out_at) {
                if ($locked->check_out_at->lt($locked->check_in_at)) {
                    throw ValidationException::withMessages([
                        'check_out_at' => __('hr.validation.check_out_before_check_in'),
                    ]);
                }
                $locked->worked_minutes = (int) $locked->check_in_at->diffInMinutes($locked->check_out_at);
            }

            $locked->source = AttendanceSource::Admin;
            $locked->adjusted_by = Auth::guard('tenant')->id();
            $locked->adjusted_at = now();
            $locked->admin_note = $note ?: $locked->admin_note;
            $meta = $locked->meta ?? [];
            $meta['adjustments'][] = [
                'at' => now()->toIso8601String(),
                'by' => $locked->adjusted_by,
                'previous' => $previous,
                'note' => $note,
            ];
            $locked->meta = $meta;
            $locked->save();

            return $locked->fresh();
        });
    }
}
