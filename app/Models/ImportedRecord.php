<?php

namespace App\Models;

use App\Enums\ImportedRecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ImportedRecord extends Model
{
    /** @use HasFactory<\Database\Factories\ImportedRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'import_history_id',
        'recordable_type',
        'recordable_id',
        'status',
        'original_data',
    ];

    protected function casts(): array
    {
        return [
            'original_data' => 'array',
            'status' => ImportedRecordStatus::class,
        ];
    }

    public function importHistory(): BelongsTo
    {
        return $this->belongsTo(ImportHistory::class);
    }

    public function recordable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isProcessed(): bool
    {
        return $this->status === ImportedRecordStatus::Processed;
    }
}
