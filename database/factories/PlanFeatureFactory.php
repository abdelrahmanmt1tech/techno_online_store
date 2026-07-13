<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\PlanFeature;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFeatureFactory extends Factory
{
    protected $model = PlanFeature::class;

    public function definition(): array
    {
        return [
            'plan_id' => Plan::factory(),
            'name' => ['en' => fake()->word(), 'ar' => fake()->word()],
            'is_active' => true,
            'order' => fake()->numberBetween(0, 100),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
