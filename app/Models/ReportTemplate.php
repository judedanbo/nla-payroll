<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\ReportTemplateFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'type',
        'query_template',
        'parameters_schema',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parameters_schema' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get all reports generated from this template.
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'report_template_id');
    }

    /**
     * Check if the template is active.
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Activate the template.
     */
    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    /**
     * Deactivate the template.
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Get the required parameters for this template.
     */
    public function getRequiredParameters(): array
    {
        $schema = $this->parameters_schema ?? [];

        return collect($schema)
            ->filter(fn ($param) => ($param['required'] ?? false) === true)
            ->keys()
            ->all();
    }

    /**
     * Validate parameters against the schema.
     */
    public function validateParameters(array $parameters): bool
    {
        $required = $this->getRequiredParameters();

        foreach ($required as $key) {
            if (! isset($parameters[$key])) {
                return false;
            }
        }

        return true;
    }
}
