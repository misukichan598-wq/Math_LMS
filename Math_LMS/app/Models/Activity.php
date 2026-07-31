<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_section_id',
        'title',
        'instructions',
        'type',
        'order',
        'passing_score',
        'is_required',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'order' => 'integer',
            'passing_score' => 'integer',
        ];
    }

    // Relationships
    public function lessonSection()
    {
        return $this->belongsTo(LessonSection::class);
    }

    public function questions()
    {
        return $this->hasMany(ActivityQuestion::class)->orderBy('order');
    }

    public function attempts()
    {
        return $this->hasMany(ActivityAttempt::class);
    }

    // Helper methods
    public function getTotalPointsAttribute(): int
    {
        return $this->questions()->sum('points');
    }

    public function getStudentScore(int $userId): int
    {
        return $this->attempts()
            ->where('user_id', $userId)
            ->sum('points_earned');
    }

    public function getStudentAccuracy(int $userId): float
    {
        $totalAttempts = $this->attempts()
            ->where('user_id', $userId)
            ->count();

        if ($totalAttempts === 0) {
            return 0;
        }

        $correctAttempts = $this->attempts()
            ->where('user_id', $userId)
            ->where('is_correct', true)
            ->count();

        return round(($correctAttempts / $totalAttempts) * 100, 2);
    }

    public function isCompletedBy(int $userId): bool
    {
        $totalQuestions = $this->questions()->count();
        $answeredQuestions = $this->attempts()
            ->where('user_id', $userId)
            ->distinct('activity_question_id')
            ->count('activity_question_id');

        return $totalQuestions === $answeredQuestions;
    }

    public function hasPassedBy(int $userId): bool
    {
        if (!$this->isCompletedBy($userId)) {
            return false;
        }

        $score = $this->getStudentScore($userId);
        $totalPoints = $this->getTotalPointsAttribute();

        if ($totalPoints === 0) {
            return false;
        }

        $percentage = ($score / $totalPoints) * 100;
        return $percentage >= $this->passing_score;
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'multiple_choice' => 'Multiple Choice',
            'true_false' => 'True or False',
            'matching' => 'Matching Type',
            'fill_blank' => 'Fill in the Blank',
            'drag_drop' => 'Drag and Drop',
            'arrange_order' => 'Arrange in Order',
            'identify' => 'Identify the Correct Answer',
            default => ucfirst($this->type),
        };
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
