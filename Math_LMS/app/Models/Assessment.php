<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'type',
        'time_limit',
        'passing_score',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'time_limit' => 'integer',
            'passing_score' => 'integer',
        ];
    }

    // Relationships
    public function questions()
    {
        return $this->hasMany(AssessmentQuestion::class)->orderBy('order');
    }

    public function attempts()
    {
        return $this->hasMany(AssessmentAttempt::class);
    }

    // Helper methods
    public function getTotalPointsAttribute(): int
    {
        return $this->questions()->sum('points');
    }

    public function getTotalQuestionsAttribute(): int
    {
        return $this->questions()->count();
    }

    public function getStudentAttempt(int $userId): ?AssessmentAttempt
    {
        return $this->attempts()
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->latest()
            ->first();
    }

    public function hasBeenCompletedBy(int $userId): bool
    {
        return $this->attempts()
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->exists();
    }

    public function getTypeLabelAttribute(): string
    {
        return ucfirst($this->type) . ' Assessment';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInitial($query)
    {
        return $query->where('type', 'initial');
    }

    public function scopeFinal($query)
    {
        return $query->where('type', 'final');
    }
}
