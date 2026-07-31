<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id',
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
    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function attempts()
    {
        return $this->hasMany(ActivityAttempt::class);
    }

    // Helper methods
    public function checkAnswer(string $answer): bool
    {
        return trim(strtolower($answer)) === trim(strtolower($this->correct_answer));
    }

    public function getStudentAnswer(int $userId): ?ActivityAttempt
    {
        return $this->attempts()
            ->where('user_id', $userId)
            ->latest()
            ->first();
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
