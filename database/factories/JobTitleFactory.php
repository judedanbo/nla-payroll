<?php

namespace Database\Factories;

use App\Models\JobGrade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JobTitle>
 */
class JobTitleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'job_grade_id' => JobGrade::factory(),
            'name' => fake()->jobTitle(),
            'code' => strtoupper(fake()->unique()->lexify('JT-???')),
            'description' => fake()->optional()->sentence(),
            'responsibilities' => fake()->optional()->paragraph(),
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
