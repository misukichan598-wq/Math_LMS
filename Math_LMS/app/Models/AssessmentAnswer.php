<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_attempt_id',
        'assessment_question_id',
        'student_answer',
        'is_correct',
        'points_earned',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'points_earned' => 'integer',
        ];
    }

    // Relationships
    public function attempt()
    {
        return $this->belongsTo(AssessmentAttempt::class, 'assessment_attempt_id');
    }

    public function question()
    {
        return $this->belongsTo(AssessmentQuestion::class, 'assessment_question_id');
    }

    // Helper methods
    public function scopeCorrect($query)
    {
        return $query->where('is_correct', true);
    }

    public function scopeIncorrect($query)
    {
        return $query->where('is_correct', false);
    }
}
