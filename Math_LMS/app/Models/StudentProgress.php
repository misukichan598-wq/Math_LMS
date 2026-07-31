<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lesson_id',
        'lesson_section_id',
        'status',
        'progress_percentage',
        'started_at',
        'completed_at',
        'time_spent',
    ];

    protected function casts(): array
    {
        return [
            'progress_percentage' => 'decimal:2',
            'time_spent' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function lessonSection()
    {
        return $this->belongsTo(LessonSection::class);
    }

    // Helper methods
    public function getTimeSpentFormattedAttribute(): string
    {
        if (!$this->time_spent) {
            return '0 min';
        }

        $hours = floor($this->time_spent / 3600);
        $minutes = floor(($this->time_spent % 3600) / 60);

        if ($hours > 0) {
            return sprintf('%d hr %d min', $hours, $minutes);
        }

        return sprintf('%d min', $minutes);
    }

    public function markAsStarted(): void
    {
        if (!$this->started_at) {
            $this->update([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        }
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'progress_percentage' => 100,
            'completed_at' => now(),
        ]);
    }

    public function addTimeSpent(int $seconds): void
    {
        $this->increment('time_spent', $seconds);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeNotStarted($query)
    {
        return $query->where('status', 'not_started');
    }
}
