<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HeadcountVerification extends Model
{
    /** @use HasFactory<\Database\Factories\HeadcountVerificationFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'headcount_session_id',
        'staff_id',
        'verified_by',
        'verified_at',
        'verification_status',
        'location',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    // Relationships

    public function headcountSession(): BelongsTo
    {
        return $this->belongsTo(HeadcountSession::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(VerificationPhoto::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(VerificationNote::class);
    }

    // Business Methods

    /**
     * Check if staff was present during verification.
     */
    public function isPresent(): bool
    {
        return $this->verification_status === 'present';
    }

    /**
     * Check if staff was identified as ghost employee.
     */
    public function isGhost(): bool
    {
        return $this->verification_status === 'ghost';
    }

    /**
     * Mark staff as ghost employee.
     */
    public function markAsGhost(?string $reason = null): void
    {
        $this->update([
            'verification_status' => 'ghost',
            'remarks' => $reason ?? $this->remarks,
        ]);

        // Also update the staff record
        $this->staff->flagAsGhost($reason);
    }

    /**
     * Add a photo to this verification.
     */
    public function addPhoto(string $photoPath, string $type = 'verification', ?string $caption = null): VerificationPhoto
    {
        return $this->photos()->create([
            'photo_path' => $photoPath,
            'photo_type' => $type,
            'caption' => $caption,
        ]);
    }

    /**
     * Add a note to this verification.
     */
    public function addNote(int $createdBy, string $content, string $type = 'general'): VerificationNote
    {
        return $this->notes()->create([
            'created_by' => $createdBy,
            'note_content' => $content,
            'note_type' => $type,
        ]);
    }
}
