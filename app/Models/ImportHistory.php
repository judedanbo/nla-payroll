<?php

namespace App\Models;

use App\Enums\ImportStatus;
use App\Enums\ImportType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImportHistory extends Model
{
    /** @use HasFactory<\Database\Factories\ImportHistoryFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uploaded_by',
        'file_name',
        'file_path',
        'import_type',
        'total_records',
        'successful_records',
        'failed_records',
        'status',
        'started_at',
        'completed_at',
        'rolled_back_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'rolled_back_at' => 'datetime',
            'status' => ImportStatus::class,
            'import_type' => ImportType::class,
        ];
    }

    /**
     * Accessor for user_id to maintain compatibility with tests
     */
    public function getUserIdAttribute(): ?int
    {
        return $this->uploaded_by;
    }

    /**
     * Mutator for user_id to maintain compatibility with tests
     */
    public function setUserIdAttribute(?int $value): void
    {
        $this->attributes['uploaded_by'] = $value;
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Alias for uploadedBy() to maintain controller compatibility
     */
    public function user(): BelongsTo
    {
        return $this->uploadedBy();
    }

    public function errors(): HasMany
    {
        return $this->hasMany(ImportError::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(ImportedRecord::class);
    }

    /**
     * Alias for records() to maintain controller compatibility
     */
    public function importedRecords(): HasMany
    {
        return $this->records();
    }

    public function isCompleted(): bool
    {
        return $this->status === ImportStatus::Completed;
    }

    public function getSuccessRate(): float
    {
        if ($this->total_records === 0) {
            return 0;
        }

        return round(($this->successful_records / $this->total_records) * 100, 2);
    }
}
