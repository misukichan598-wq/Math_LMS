<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'activity_id',
        'activity_question_id',
        'student_answer',
        'is_correct',
        'points_earned',
        'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'points_earned' => 'integer',
            'answered_at' => 'datetime',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function question()
    {
        return $this->belongsTo(ActivityQuestion::class, 'activity_question_id');
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
