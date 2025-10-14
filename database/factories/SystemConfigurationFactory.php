<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SystemConfiguration>
 */
class SystemConfigurationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $configs = [
            [
                'key' => 'app.maintenance_mode',
                'value' => '0',
                'type' => 'boolean',
                'description' => 'Enable or disable maintenance mode',
                'is_public' => true,
            ],
            [
                'key' => 'audit.verification_timeout_days',
                'value' => '30',
                'type' => 'integer',
                'description' => 'Number of days before verification expires',
                'is_public' => false,
            ],
            [
                'key' => 'system.max_import_rows',
                'value' => '10000',
                'type' => 'integer',
                'description' => 'Maximum number of rows allowed in CSV import',
                'is_public' => false,
            ],
            [
                'key' => 'notifications.enable_email',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Enable email notifications',
                'is_public' => true,
            ],
            [
                'key' => 'reports.retention_days',
                'value' => '365',
                'type' => 'integer',
                'description' => 'Number of days to retain generated reports',
                'is_public' => false,
            ],
        ];

        $config = fake()->randomElement($configs);

        return [
            'key' => $config['key'],
            'value' => $config['value'],
            'type' => $config['type'],
            'description' => $config['description'],
            'is_public' => $config['is_public'],
        ];
    }

    /**
     * Indicate the configuration is public.
     */
    public function publicConfig(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }

    /**
     * Indicate the configuration is private.
     */
    public function privateConfig(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }

    /**
     * Create a boolean configuration.
     */
    public function boolean(string $key, bool $value, ?string $description = null): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => $key,
            'value' => $value ? '1' : '0',
            'type' => 'boolean',
            'description' => $description ?? 'Boolean configuration',
        ]);
    }

    /**
     * Create an integer configuration.
     */
    public function integer(string $key, int $value, ?string $description = null): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => $key,
            'value' => (string) $value,
            'type' => 'integer',
            'description' => $description ?? 'Integer configuration',
        ]);
    }

    /**
     * Create a string configuration.
     */
    public function string(string $key, string $value, ?string $description = null): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => $key,
            'value' => $value,
            'type' => 'string',
            'description' => $description ?? 'String configuration',
        ]);
    }
}
