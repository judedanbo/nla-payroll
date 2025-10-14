<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Region>
 */
class RegionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Greater Accra',
                'Ashanti',
                'Western',
                'Eastern',
                'Central',
                'Northern',
                'Upper East',
                'Upper West',
                'Volta',
                'Brong Ahafo',
            ]).' Region',
            'code' => strtoupper(fake()->unique()->lexify('RG-???')),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
