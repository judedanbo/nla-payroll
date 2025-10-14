<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class VerificationPhoto extends Model
{
    /** @use HasFactory<\Database\Factories\VerificationPhotoFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'headcount_verification_id',
        'photo_path',
        'photo_type',
        'caption',
    ];

    // Relationships

    public function headcountVerification(): BelongsTo
    {
        return $this->belongsTo(HeadcountVerification::class);
    }

    // Business Methods

    /**
     * Get the full URL to the photo.
     */
    public function getPhotoUrl(): string
    {
        return Storage::url($this->photo_path);
    }

    /**
     * Check if the photo file exists.
     */
    public function photoExists(): bool
    {
        return Storage::exists($this->photo_path);
    }

    /**
     * Delete the photo file from storage.
     */
    public function deletePhotoFile(): bool
    {
        if ($this->photoExists()) {
            return Storage::delete($this->photo_path);
        }

        return false;
    }

    /**
     * Get file size in bytes.
     */
    public function getFileSize(): ?int
    {
        if ($this->photoExists()) {
            return Storage::size($this->photo_path);
        }

        return null;
    }

    /**
     * Get human-readable file size.
     */
    public function getHumanFileSize(): string
    {
        $bytes = $this->getFileSize();

        if ($bytes === null) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
