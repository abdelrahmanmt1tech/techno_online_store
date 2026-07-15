<?php

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

class FaqFactory extends Factory
{
    protected $model = Faq::class;

    public function definition(): array
    {
        return [
            'question' => ['en' => fake()->sentence().'?', 'ar' => fake()->sentence().'؟'],
            'answer' => ['en' => fake()->paragraph(), 'ar' => fake()->paragraph()],
            'order' => fake()->numberBetween(0, 100),
            'is_active' => true,
            'faqable_type' => null,
            'faqable_id' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function forModel(string $type, int $id): static
    {
        return $this->state(fn (array $attributes) => [
            'faqable_type' => $type,
            'faqable_id' => $id,
        ]);
    }
}
