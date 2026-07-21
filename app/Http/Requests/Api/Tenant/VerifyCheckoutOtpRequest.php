<?php

namespace App\Http\Requests\Api\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class VerifyCheckoutOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|max:255',
            'code' => 'required|string|size:6',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:255',
            'customer_address' => 'required|string',
            'payment_method' => 'required|in:cash,online',
            'governorate_id' => 'nullable|exists:governorates,id',
            'coupon_code' => 'nullable|string',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
