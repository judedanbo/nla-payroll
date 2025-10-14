<?php

namespace App\Models;

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
        'total_records',
        'successful_records',
        'failed_records',
        'status',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function errors(): HasMany
    {
        return $this->hasMany(ImportError::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(ImportedRecord::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function getSuccessRate(): float
    {
        if ($this->total_records === 0) {
            return 0;
        }

        return round(($this->successful_records / $this->total_records) * 100, 2);
    }
}
