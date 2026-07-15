<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Stancl\Tenancy\Database\Models\Domain;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $centralDomain = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';

        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:tenants,email',
            'phone' => 'nullable|string|max:50|unique:tenants,phone',
            'password' => 'required|string|min:8|max:255|confirmed',
            'password_confirmation' => 'required|string',

            'subdomain' => [
                'required',
                'string',
                'max:63',
                'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/',
                function (string $attribute, mixed $value, \Closure $fail) use ($centralDomain): void {
                    $fullDomain = $value.'.'.$centralDomain;

                    if (Domain::where('domain', $fullDomain)->exists()) {
                        $fail(__('dashboard.domain_taken'));
                    }
                },
            ],

            'country_name' => 'nullable|string|max:255',
            'currency_code' => 'nullable|string|max:10',

            'plan_id' => 'nullable|exists:plans,id',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'started_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:started_at',
        ];
    }
}
