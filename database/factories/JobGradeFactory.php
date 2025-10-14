<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JobGrade>
 */
class JobGradeFactory extends Factory
{
    public function definition(): array
    {
        $minSalary = fake()->numberBetween(1000, 5000);
        $maxSalary = $minSalary + fake()->numberBetween(2000, 10000);

        return [
            'name' => 'Grade '.fake()->randomElement(['A', 'B', 'C', 'D', 'E']).fake()->numberBetween(1, 10),
            'code' => strtoupper(fake()->unique()->lexify('GR-???')),
            'description' => fake()->optional()->sentence(),
            'level' => fake()->numberBetween(1, 15),
            'min_salary' => $minSalary,
            'max_salary' => $maxSalary,
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
