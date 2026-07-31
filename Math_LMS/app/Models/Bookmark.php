<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bookmark extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lesson_id',
        'lesson_section_id',
        'note',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function lessonSection()
    {
        return $this->belongsTo(LessonSection::class);
    }

    // Helper methods
    public function getLocationAttribute(): string
    {
        if ($this->lessonSection) {
            return $this->lesson->title . ' - ' . $this->lessonSection->title;
        }
        return $this->lesson->title;
    }
}
