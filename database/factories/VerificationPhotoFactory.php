<?php

namespace Database\Factories;

use App\Models\HeadcountVerification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VerificationPhoto>
 */
class VerificationPhotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'headcount_verification_id' => HeadcountVerification::factory(),
            'photo_path' => 'verification_photos/'.fake()->uuid().'.jpg',
            'photo_type' => fake()->randomElement(['verification', 'id_card', 'workstation']),
            'caption' => fake()->optional(0.5)->sentence(),
        ];
    }

    /**
     * Indicate that this is a verification photo.
     */
    public function verificationType(): static
    {
        return $this->state(fn (array $attributes) => [
            'photo_type' => 'verification',
            'caption' => 'Staff verification photo',
        ]);
    }

    /**
     * Indicate that this is an ID card photo.
     */
    public function idCard(): static
    {
        return $this->state(fn (array $attributes) => [
            'photo_type' => 'id_card',
            'caption' => 'Staff ID card photo',
        ]);
    }

    /**
     * Indicate that this is a workstation photo.
     */
    public function workstation(): static
    {
        return $this->state(fn (array $attributes) => [
            'photo_type' => 'workstation',
            'caption' => 'Workstation photo',
        ]);
    }
}
