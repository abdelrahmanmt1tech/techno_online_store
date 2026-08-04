<?php

namespace App\Http\Requests\Central;

use App\Models\PackagePrice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
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

            'period' => 'required|string|in:monthly,yearly',

            'packages' => 'required|array|min:1',
            'packages.*.package_id' => 'required|integer|exists:packages,id',
            'packages.*.price_id' => 'required|integer|exists:prices,id',
            'started_at' => 'nullable|date',
        ];
    }

    /**
     * Generalized cross-field check: every package's `price_id` must belong to
     * its own `package_id`.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ($this->input('packages', []) as $index => $item) {
                $priceId = $item['price_id'] ?? null;
                $packageId = $item['package_id'] ?? null;

                if (! $priceId || ! $packageId) {
                    continue;
                }

                $price = PackagePrice::find($priceId);

                if ($price && $price->package_id != $packageId) {
                    $validator->errors()->add(
                        "packages.{$index}.price_id",
                        __('dashboard.invalid_package_price')
                    );
                }
            }
        });
    }
}
