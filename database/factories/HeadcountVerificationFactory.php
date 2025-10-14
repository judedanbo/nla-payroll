<?php

namespace Database\Factories;

use App\Models\HeadcountSession;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HeadcountVerification>
 */
class HeadcountVerificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'headcount_session_id' => HeadcountSession::factory(),
            'staff_id' => Staff::factory(),
            'verified_by' => User::factory(),
            'verified_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'verification_status' => fake()->randomElement(['present', 'present', 'present', 'absent', 'on_leave']), // 60% present
            'location' => fake()->optional(0.6)->randomElement([
                'NLA Head Office - Accra',
                'Tema Office',
                'Kumasi Regional Office',
                'Takoradi Office',
            ]),
            'remarks' => fake()->optional(0.4)->sentence(),
        ];
    }

    /**
     * Indicate that the staff was present.
     */
    public function present(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'present',
        ]);
    }

    /**
     * Indicate that the staff was absent.
     */
    public function absent(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'absent',
            'remarks' => fake()->randomElement([
                'Not present during verification',
                'Could not be located',
                'Did not report to work',
            ]),
        ]);
    }

    /**
     * Indicate that the staff is a ghost employee.
     */
    public function ghost(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'ghost',
            'remarks' => fake()->randomElement([
                'No record of employee at station',
                'Colleagues do not recognize employee',
                'Position does not exist at this location',
                'Suspected fraudulent entry',
            ]),
        ]);
    }
}
