<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['info', 'warning', 'success', 'error']);
        $titles = [
            'info' => ['New Assignment', 'System Update', 'Data Import Complete'],
            'warning' => ['Pending Verification', 'Missing Data', 'Session Expiring Soon'],
            'success' => ['Verification Complete', 'Report Generated', 'Data Synced'],
            'error' => ['Import Failed', 'Verification Error', 'System Error'],
        ];
        $messages = [
            'info' => ['You have been assigned to a new headcount session.', 'System maintenance scheduled for tonight.', 'Your data import has completed successfully.'],
            'warning' => ['You have pending verifications that need attention.', 'Some records are missing required data.', 'Your session will expire in 15 minutes.'],
            'success' => ['All verifications have been completed successfully.', 'Your report has been generated and is ready for download.', 'Data synchronization completed.'],
            'error' => ['The data import failed due to validation errors.', 'An error occurred during verification.', 'A system error has been logged.'],
        ];

        return [
            'user_id' => \App\Models\User::factory(),
            'type' => $type,
            'title' => fake()->randomElement($titles[$type]),
            'message' => fake()->randomElement($messages[$type]),
            'read_at' => fake()->boolean(50) ? fake()->dateTimeBetween('-7 days', 'now') : null,
            'action_url' => fake()->boolean(60) ? fake()->randomElement(['/dashboard', '/verifications', '/reports', '/sessions']) : null,
        ];
    }

    /**
     * Indicate the notification has been read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Indicate the notification is unread.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }

    /**
     * Indicate the notification is of type info.
     */
    public function info(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'info',
            'title' => 'New Assignment',
            'message' => 'You have been assigned to a new headcount session.',
        ]);
    }

    /**
     * Indicate the notification is of type warning.
     */
    public function warning(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'warning',
            'title' => 'Pending Verification',
            'message' => 'You have pending verifications that need attention.',
        ]);
    }

    /**
     * Indicate the notification is of type success.
     */
    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'success',
            'title' => 'Verification Complete',
            'message' => 'All verifications have been completed successfully.',
        ]);
    }

    /**
     * Indicate the notification is of type error.
     */
    public function error(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'error',
            'title' => 'Import Failed',
            'message' => 'The data import failed due to validation errors.',
        ]);
    }
}
