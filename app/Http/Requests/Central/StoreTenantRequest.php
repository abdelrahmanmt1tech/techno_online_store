<?php

namespace App\Http\Requests\Central;

use App\Models\PackagePrice;
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

            'country_id' => 'nullable|exists:countries,id',
            'currency_id' => 'nullable|exists:currencies,id',

            'payment_method' => 'required|string|in:online,offline',
            'terms_accepted' => 'required|boolean',

            'packages' => 'required|array|min:1',
            'packages.*.package_id' => 'required|integer|exists:packages,id',
            'packages.*.price_id' => [
                'required',
                'integer',
                'exists:prices,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $index = (int) str_replace(['packages.', '.price_id'], '', $attribute);
                    $packageId = $this->input("packages.{$index}.package_id");

                    if (! $packageId) {
                        return;
                    }

                    $price = PackagePrice::find($value);

                    if (! $price || $price->package_id != $packageId) {
                        $fail(__('dashboard.invalid_package_price'));
                    }
                },
            ],
            'started_at' => 'nullable|date',
        ];
    }
}
