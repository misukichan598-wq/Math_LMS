<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'profile_picture',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function studentProfile()
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function progress()
    {
        return $this->hasMany(StudentProgress::class);
    }

    public function activityAttempts()
    {
        return $this->hasMany(ActivityAttempt::class);
    }

    public function assessmentAttempts()
    {
        return $this->hasMany(AssessmentAttempt::class);
    }

    public function scores()
    {
        return $this->hasMany(StudentScore::class);
    }

    public function hallOfFame()
    {
        return $this->hasOne(HallOfFame::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function learningHistory()
    {
        return $this->hasMany(LearningHistory::class);
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }

    public function announcements()
    {
        return $this->hasMany(Announcement::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    // Helper methods
    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function getProfilePictureUrlAttribute(): ?string
    {
        if ($this->profile_picture) {
            return asset('storage/' . $this->profile_picture);
        }
        return asset('images/default-avatar.png');
    }

    public function hasCompletedInitialAssessment(): bool
    {
        return $this->assessmentAttempts()
            ->whereHas('assessment', function ($query) {
                $query->where('type', 'initial');
            })
            ->where('status', 'completed')
            ->exists();
    }

    public function hasCompletedFinalAssessment(): bool
    {
        return $this->assessmentAttempts()
            ->whereHas('assessment', function ($query) {
                $query->where('type', 'final');
            })
            ->where('status', 'completed')
            ->exists();
    }

    public function getOverallProgress(): float
    {
        $totalLessons = Lesson::where('is_active', true)->count();
        if ($totalLessons === 0) {
            return 0;
        }

        $completedLessons = $this->progress()
            ->where('status', 'completed')
            ->distinct('lesson_id')
            ->count('lesson_id');

        return round(($completedLessons / $totalLessons) * 100, 2);
    }
}
