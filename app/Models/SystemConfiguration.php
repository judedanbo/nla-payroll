<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemConfiguration extends Model
{
    /** @use HasFactory<\Database\Factories\SystemConfigurationFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'is_public',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    /**
     * Get the typed value based on the type field.
     */
    public function getTypedValue(): mixed
    {
        return match ($this->type) {
            'integer' => (int) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    /**
     * Set a configuration value with proper type casting.
     */
    public static function set(string $key, mixed $value, string $type = 'string', ?string $description = null, bool $isPublic = false): self
    {
        $stringValue = match ($type) {
            'integer' => (string) $value,
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value,
        };

        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $stringValue,
                'type' => $type,
                'description' => $description,
                'is_public' => $isPublic,
            ]
        );
    }

    /**
     * Get a configuration value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $config = self::where('key', $key)->first();

        if (! $config) {
            return $default;
        }

        return $config->getTypedValue();
    }

    /**
     * Check if a configuration key exists.
     */
    public static function has(string $key): bool
    {
        return self::where('key', $key)->exists();
    }

    /**
     * Check if this configuration is public (visible to non-admin users).
     */
    public function isPublic(): bool
    {
        return $this->is_public === true;
    }
}
