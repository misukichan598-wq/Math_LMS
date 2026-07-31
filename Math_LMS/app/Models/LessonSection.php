<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'title',
        'content',
        'order',
        'has_activity',
        'is_required',
    ];

    protected function casts(): array
    {
        return [
            'has_activity' => 'boolean',
            'is_required' => 'boolean',
            'order' => 'integer',
        ];
    }

    // Relationships
    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class)->orderBy('order');
    }

    public function progress()
    {
        return $this->hasMany(StudentProgress::class);
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }

    // Helper methods
    public function isCompletedBy(int $userId): bool
    {
        return $this->progress()
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->exists();
    }

    public function getNextSection(): ?LessonSection
    {
        return LessonSection::where('lesson_id', $this->lesson_id)
            ->where('order', '>', $this->order)
            ->orderBy('order')
            ->first();
    }

    public function getPreviousSection(): ?LessonSection
    {
        return LessonSection::where('lesson_id', $this->lesson_id)
            ->where('order', '<', $this->order)
            ->orderBy('order', 'desc')
            ->first();
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }
}
