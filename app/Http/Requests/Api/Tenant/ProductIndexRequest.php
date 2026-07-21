<?php

namespace App\Http\Requests\Api\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class ProductIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'nullable|array',
            'category_id.*' => 'integer',
            'search' => 'nullable|string|max:255',
            'sort' => 'nullable|string|in:popular,newest,price_high,price_low',
            'per_page' => 'nullable|integer|min:1|max:50',
        ];
    }
}
