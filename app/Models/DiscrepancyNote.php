<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscrepancyNote extends Model
{
    /** @use HasFactory<\Database\Factories\DiscrepancyNoteFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'discrepancy_id',
        'created_by',
        'note_content',
        'is_internal',
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
        ];
    }

    // Relationships

    public function discrepancy(): BelongsTo
    {
        return $this->belongsTo(Discrepancy::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Business Methods

    /**
     * Check if note is internal only.
     */
    public function isInternal(): bool
    {
        return $this->is_internal;
    }

    /**
     * Get a preview of the note content.
     */
    public function getPreview(int $length = 100): string
    {
        if (strlen($this->note_content) <= $length) {
            return $this->note_content;
        }

        return substr($this->note_content, 0, $length).'...';
    }
}
