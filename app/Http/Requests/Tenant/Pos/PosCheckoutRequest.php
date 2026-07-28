<?php

namespace App\Http\Requests\Tenant\Pos;

use Illuminate\Foundation\Http\FormRequest;

class PosCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('tenant')->check();
    }

    public function rules(): array
    {
        return [
            'register_id' => ['required', 'integer'],
            'customer_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'discount_total' => ['nullable', 'numeric', 'min:0'],
            'currency_code' => ['nullable', 'string', 'max:8'],
            'idempotency_key' => ['required', 'string', 'max:120'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.product_variant_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.type' => ['required', 'string', 'in:cash,card,transfer,other,wallet'],
            'payments.*.code' => ['nullable', 'string', 'max:50'],
            'payments.*.amount' => ['required', 'numeric', 'gt:0'],
            'payments.*.reference' => ['nullable', 'string', 'max:120'],
            'payments.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
