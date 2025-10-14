<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    /** @use HasFactory<\Database\Factories\NotificationFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'read_at',
        'action_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /**
     * Get the user who receives this notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the notification has been read.
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Check if the notification is unread.
     */
    public function isUnread(): bool
    {
        return ! $this->isRead();
    }

    /**
     * Mark the notification as read.
     */
    public function markAsRead(): bool
    {
        if ($this->isRead()) {
            return true;
        }

        return $this->update(['read_at' => now()]);
    }

    /**
     * Mark the notification as unread.
     */
    public function markAsUnread(): bool
    {
        if ($this->isUnread()) {
            return true;
        }

        return $this->update(['read_at' => null]);
    }

    /**
     * Check if notification is of type info.
     */
    public function isInfo(): bool
    {
        return $this->type === 'info';
    }

    /**
     * Check if notification is of type warning.
     */
    public function isWarning(): bool
    {
        return $this->type === 'warning';
    }

    /**
     * Check if notification is of type success.
     */
    public function isSuccess(): bool
    {
        return $this->type === 'success';
    }

    /**
     * Check if notification is of type error.
     */
    public function isError(): bool
    {
        return $this->type === 'error';
    }
}
