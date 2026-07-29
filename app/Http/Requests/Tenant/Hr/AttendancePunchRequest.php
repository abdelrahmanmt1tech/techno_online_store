<?php

namespace App\Http\Requests\Tenant\Hr;

use Illuminate\Foundation\Http\FormRequest;

class AttendancePunchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth('tenant')->user();
        if (! $user) {
            return false;
        }

        if ($this->routeIs('filament.tenant.hr.attendance.check-out')) {
            return $user->can('hr.attendance.check_out');
        }

        return $user->can('hr.attendance.check_in');
    }

    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'captured_at' => ['nullable', 'date'],
        ];
    }
}
