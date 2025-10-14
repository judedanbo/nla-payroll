<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VerificationNote extends Model
{
    /** @use HasFactory<\Database\Factories\VerificationNoteFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'headcount_verification_id',
        'created_by',
        'note_content',
        'note_type',
    ];

    // Relationships

    public function headcountVerification(): BelongsTo
    {
        return $this->belongsTo(HeadcountVerification::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Business Methods

    /**
     * Check if this is a discrepancy note.
     */
    public function isDiscrepancy(): bool
    {
        return $this->note_type === 'discrepancy';
    }

    /**
     * Check if this is a concern note.
     */
    public function isConcern(): bool
    {
        return $this->note_type === 'concern';
    }

    /**
     * Get a preview of the note content (first 100 characters).
     */
    public function getPreview(int $length = 100): string
    {
        if (strlen($this->note_content) <= $length) {
            return $this->note_content;
        }

        return substr($this->note_content, 0, $length).'...';
    }
}
