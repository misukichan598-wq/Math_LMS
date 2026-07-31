<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'assessment_id',
        'score',
        'total_questions',
        'correct_answers',
        'time_taken',
        'started_at',
        'completed_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'total_questions' => 'integer',
            'correct_answers' => 'integer',
            'time_taken' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }

    public function answers()
    {
        return $this->hasMany(AssessmentAnswer::class);
    }

    // Helper methods
    public function getPercentageAttribute(): float
    {
        if ($this->total_questions === 0) {
            return 0;
        }
        return round(($this->correct_answers / $this->total_questions) * 100, 2);
    }

    public function hasPassed(): bool
    {
        return $this->percentage >= $this->assessment->passing_score;
    }

    public function getTimeTakenFormattedAttribute(): string
    {
        if (!$this->time_taken) {
            return 'N/A';
        }

        $minutes = floor($this->time_taken / 60);
        $seconds = $this->time_taken % 60;

        return sprintf('%d min %d sec', $minutes, $seconds);
    }

    public function calculateScore(): void
    {
        $correctAnswers = $this->answers()->where('is_correct', true)->count();
        $totalPoints = $this->answers()->sum('points_earned');
        $maxPoints = $this->assessment->getTotalPointsAttribute();

        $this->update([
            'correct_answers' => $correctAnswers,
            'score' => $maxPoints > 0 ? round(($totalPoints / $maxPoints) * 100, 2) : 0,
        ]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }
}
