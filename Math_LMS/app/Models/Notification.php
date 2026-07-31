<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'link',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper methods
    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'lesson' => 'fa-book',
            'announcement' => 'fa-bullhorn',
            'achievement' => 'fa-trophy',
            'assessment' => 'fa-clipboard-check',
            'reminder' => 'fa-bell',
            default => 'fa-info-circle',
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'lesson' => 'primary',
            'announcement' => 'info',
            'achievement' => 'success',
            'assessment' => 'warning',
            'reminder' => 'secondary',
            default => 'light',
        };
    }

    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    public static function createForUser(int $userId, string $title, string $message, string $type = 'lesson', ?string $link = null): self
    {
        return self::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => $link,
        ]);
    }
}
