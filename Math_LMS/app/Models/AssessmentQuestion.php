<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_id',
        'question',
        'options',
        'correct_answer',
        'explanation',
        'points',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'points' => 'integer',
            'order' => 'integer',
        ];
    }

    // Relationships
    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }

    public function answers()
    {
        return $this->hasMany(AssessmentAnswer::class);
    }

    // Helper methods
    public function checkAnswer(string $answer): bool
    {
        return trim(strtolower($answer)) === trim(strtolower($this->correct_answer));
    }

    public function getStudentAnswer(int $attemptId): ?AssessmentAnswer
    {
        return $this->answers()
            ->where('assessment_attempt_id', $attemptId)
            ->first();
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
