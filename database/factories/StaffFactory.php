<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\JobTitle;
use App\Models\Station;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Staff>
 */
class StaffFactory extends Factory
{
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'user_id' => null,
            'department_id' => Department::factory(),
            'unit_id' => Unit::factory(),
            'job_title_id' => JobTitle::factory(),
            'station_id' => Station::factory(),
            'staff_number' => strtoupper(fake()->unique()->bothify('STF-####-??')),
            'first_name' => $firstName,
            'middle_name' => fake()->optional(0.6)->firstName(),
            'last_name' => $lastName,
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-20 years'),
            'national_id' => 'GHA-'.fake()->unique()->numerify('###########'),
            'gender' => fake()->randomElement(['male', 'female']),
            'marital_status' => fake()->randomElement(['single', 'married', 'divorced', 'widowed']),
            'email' => fake()->optional(0.7)->safeEmail(),
            'phone_primary' => fake()->numerify('+233#########'),
            'phone_secondary' => fake()->optional(0.4)->numerify('+233#########'),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'region' => fake()->state(),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->numerify('+233#########'),
            'date_of_hire' => fake()->dateTimeBetween('-15 years', '-1 year'),
            'date_of_termination' => null,
            'employment_status' => 'active',
            'employment_type' => fake()->randomElement(['permanent', 'contract', 'temporary']),
            'current_salary' => fake()->numberBetween(2000, 15000),
            'is_verified' => fake()->boolean(30),
            'last_verified_at' => fake()->optional(0.3)->dateTimeThisYear(),
            'is_ghost' => false,
            'ghost_reason' => null,
            'is_active' => true,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
            'last_verified_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    public function ghost(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_ghost' => true,
            'is_verified' => false,
            'ghost_reason' => 'Not physically present at station during headcount',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'employment_status' => fake()->randomElement(['on_leave', 'suspended', 'terminated']),
        ]);
    }

    public function terminated(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'employment_status' => 'terminated',
            'date_of_termination' => fake()->dateTimeBetween('-2 years', 'now'),
        ]);
    }
}
