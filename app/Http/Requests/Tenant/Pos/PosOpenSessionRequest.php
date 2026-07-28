<?php

namespace App\Http\Requests\Tenant\Pos;

use Illuminate\Foundation\Http\FormRequest;

class PosOpenSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('tenant')->check();
    }

    public function rules(): array
    {
        return [
            'register_id' => ['required', 'integer'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'opening_notes' => ['nullable', 'string', 'max:2000'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ];
    }
}
