<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        return [
            'key' => fake()->unique()->word(),
            'value' => fake()->text(),
            'string_value' => null,
        ];
    }

    public function stringValue(?string $value = null): static
    {
        return $this->state(fn (array $attributes) => [
            'string_value' => $value ?? fake()->randomHtml(),
        ]);
    }

    public function jsonValue(array $data): static
    {
        return $this->state(fn (array $attributes) => [
            'value' => json_encode($data),
        ]);
    }
}
