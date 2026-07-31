<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lesson_id',
        'score_type',
        'score',
        'max_score',
        'percentage',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'max_score' => 'integer',
            'percentage' => 'decimal:2',
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

    // Helper methods
    public function getScoreTypeLabelAttribute(): string
    {
        return match($this->score_type) {
            'initial_assessment' => 'Initial Assessment',
            'final_assessment' => 'Final Assessment',
            'activity' => 'Activity Score',
            'overall' => 'Overall Score',
            default => ucfirst(str_replace('_', ' ', $this->score_type)),
        };
    }

    public function getGradeAttribute(): string
    {
        return match(true) {
            $this->percentage >= 95 => 'A+',
            $this->percentage >= 90 => 'A',
            $this->percentage >= 85 => 'B+',
            $this->percentage >= 80 => 'B',
            $this->percentage >= 75 => 'C+',
            $this->percentage >= 70 => 'C',
            $this->percentage >= 65 => 'D+',
            $this->percentage >= 60 => 'D',
            default => 'F',
        };
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('score_type', $type);
    }
}
