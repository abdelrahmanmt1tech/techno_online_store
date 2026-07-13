<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'name' => ['en' => fake()->word(), 'ar' => fake()->word()],
            'title' => ['en' => fake()->sentence(), 'ar' => fake()->sentence()],
            'description' => ['en' => fake()->paragraph(), 'ar' => fake()->paragraph()],
            'type' => fake()->randomElement(['commission', 'subscription']),
            'price' => fake()->randomFloat(2, 0, 9999),
            'currency' => 'SAR',
            'commission_per_order' => fake()->optional()->randomFloat(2, 0, 100),
            'subscription_period' => fake()->optional()->randomElement(['monthly', 'yearly']),
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

    public function commission(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'commission',
            'subscription_period' => null,
        ]);
    }

    public function subscription(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'subscription',
            'subscription_period' => fake()->randomElement(['monthly', 'yearly']),
            'commission_per_order' => null,
        ]);
    }
}
