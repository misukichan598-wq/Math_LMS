<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningHistory extends Model
{
    use HasFactory;

    protected $table = 'learning_history';

    protected $fillable = [
        'user_id',
        'activity_type',
        'activity_description',
        'related_type',
        'related_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function related()
    {
        return $this->morphTo();
    }

    // Helper methods
    public function getActivityTypeIconAttribute(): string
    {
        return match($this->activity_type) {
            'lesson_start' => 'fa-play-circle',
            'lesson_complete' => 'fa-check-circle',
            'section_complete' => 'fa-check',
            'activity_complete' => 'fa-tasks',
            'assessment_start' => 'fa-clipboard',
            'assessment_complete' => 'fa-clipboard-check',
            default => 'fa-circle',
        };
    }

    public function getActivityTypeColorAttribute(): string
    {
        return match($this->activity_type) {
            'lesson_start' => 'info',
            'lesson_complete' => 'success',
            'section_complete' => 'primary',
            'activity_complete' => 'warning',
            'assessment_start' => 'secondary',
            'assessment_complete' => 'success',
            default => 'light',
        };
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('activity_type', $type);
    }

    public function scopeRecent($query, int $limit = 20)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    public static function log(
        int $userId,
        string $activityType,
        string $activityDescription,
        Model $related = null,
        array $metadata = []
    ): self {
        return self::create([
            'user_id' => $userId,
            'activity_type' => $activityType,
            'activity_description' => $activityDescription,
            'related_type' => $related ? get_class($related) : null,
            'related_id' => $related?->id,
            'metadata' => $metadata,
        ]);
    }
}
