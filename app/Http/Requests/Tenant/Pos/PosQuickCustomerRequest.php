<?php

namespace App\Http\Requests\Tenant\Pos;

use Illuminate\Foundation\Http\FormRequest;

class PosQuickCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('tenant')->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ];
    }
}
