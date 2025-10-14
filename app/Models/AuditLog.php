<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    /** @use HasFactory<\Database\Factories\AuditLogFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the auditable model (polymorphic).
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if this log represents a creation action.
     */
    public function isCreation(): bool
    {
        return $this->action === 'created';
    }

    /**
     * Check if this log represents an update action.
     */
    public function isUpdate(): bool
    {
        return $this->action === 'updated';
    }

    /**
     * Check if this log represents a deletion action.
     */
    public function isDeletion(): bool
    {
        return $this->action === 'deleted';
    }

    /**
     * Get the changes made in this audit log.
     */
    public function getChanges(): array
    {
        if (! $this->isUpdate()) {
            return [];
        }

        $changes = [];
        $old = $this->old_values ?? [];
        $new = $this->new_values ?? [];

        foreach ($new as $key => $value) {
            if (! isset($old[$key]) || $old[$key] !== $value) {
                $changes[$key] = [
                    'old' => $old[$key] ?? null,
                    'new' => $value,
                ];
            }
        }

        return $changes;
    }
}
