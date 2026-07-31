<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'content',
        'type',
        'is_active',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper methods
    public function getTypeClassAttribute(): string
    {
        return match($this->type) {
            'info' => 'alert-info',
            'warning' => 'alert-warning',
            'success' => 'alert-success',
            'danger' => 'alert-danger',
            default => 'alert-primary',
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'info' => 'fa-info-circle',
            'warning' => 'fa-exclamation-triangle',
            'success' => 'fa-check-circle',
            'danger' => 'fa-times-circle',
            default => 'fa-bell',
        };
    }

    public function isPublished(): bool
    {
        return $this->is_active && 
               $this->published_at && 
               $this->published_at->isPast();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublished($query)
    {
        return $query->where('is_active', true)
                     ->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('published_at', 'desc');
    }
}
