<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'pdf_path',
        'order',
        'is_active',
        'estimated_duration',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order' => 'integer',
            'estimated_duration' => 'integer',
        ];
    }

    // Relationships
    public function sections()
    {
        return $this->hasMany(LessonSection::class)->orderBy('order');
    }

    public function progress()
    {
        return $this->hasMany(StudentProgress::class);
    }

    public function scores()
    {
        return $this->hasMany(StudentScore::class);
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }

    // Helper methods
    public function getPdfUrlAttribute(): ?string
    {
        if ($this->pdf_path) {
            return asset('storage/' . $this->pdf_path);
        }
        return null;
    }

    public function getTotalSectionsAttribute(): int
    {
        return $this->sections()->count();
    }

    public function getTotalActivitiesAttribute(): int
    {
        return Activity::whereHas('lessonSection', function ($query) {
            $query->where('lesson_id', $this->id);
        })->count();
    }

    public function getStudentProgress(int $userId): ?StudentProgress
    {
        return $this->progress()
            ->where('user_id', $userId)
            ->whereNull('lesson_section_id')
            ->first();
    }

    public function getCompletionPercentage(int $userId): float
    {
        $totalSections = $this->sections()->count();
        if ($totalSections === 0) {
            return 0;
        }

        $completedSections = StudentProgress::where('user_id', $userId)
            ->where('lesson_id', $this->id)
            ->whereNotNull('lesson_section_id')
            ->where('status', 'completed')
            ->count();

        return round(($completedSections / $totalSections) * 100, 2);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
