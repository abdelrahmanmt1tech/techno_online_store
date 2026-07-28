<?php

namespace App\Http\Requests\Tenant\Pos;

use Illuminate\Foundation\Http\FormRequest;

class PosCloseSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('tenant')->check();
    }

    public function rules(): array
    {
        return [
            'register_id' => ['required', 'integer'],
            'actual_cash' => ['required', 'numeric', 'min:0'],
            'actual_card' => ['nullable', 'numeric', 'min:0'],
            'actual_transfer' => ['nullable', 'numeric', 'min:0'],
            'actual_other' => ['nullable', 'numeric', 'min:0'],
            'closing_notes' => ['nullable', 'string', 'max:2000'],
            'difference_reason' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'actual_card' => $this->input('actual_card', 0),
            'actual_transfer' => $this->input('actual_transfer', 0),
            'actual_other' => $this->input('actual_other', 0),
        ]);
    }
}
