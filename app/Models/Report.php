<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    /** @use HasFactory<\Database\Factories\ReportFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'report_template_id',
        'generated_by',
        'title',
        'parameters',
        'file_path',
        'generated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parameters' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    /**
     * Get the report template used to generate this report.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class, 'report_template_id');
    }

    /**
     * Get the user who generated this report.
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Check if the report has a file attached.
     */
    public function hasFile(): bool
    {
        return $this->file_path !== null;
    }

    /**
     * Get the full file URL.
     */
    public function getFileUrl(): ?string
    {
        if (! $this->hasFile()) {
            return null;
        }

        return asset('storage/'.$this->file_path);
    }

    /**
     * Get a parameter value by key.
     */
    public function getParameter(string $key, mixed $default = null): mixed
    {
        return data_get($this->parameters, $key, $default);
    }
}
