<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HallOfFame extends Model
{
    use HasFactory;

    protected $table = 'hall_of_fame';

    protected $fillable = [
        'user_id',
        'rank',
        'final_score',
        'activity_accuracy',
        'completion_rate',
        'total_learning_time',
        'improvement_percentage',
    ];

    protected function casts(): array
    {
        return [
            'rank' => 'integer',
            'final_score' => 'decimal:2',
            'activity_accuracy' => 'decimal:2',
            'completion_rate' => 'decimal:2',
            'total_learning_time' => 'integer',
            'improvement_percentage' => 'decimal:2',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper methods
    public function getTotalLearningTimeFormattedAttribute(): string
    {
        $hours = floor($this->total_learning_time / 3600);
        $minutes = floor(($this->total_learning_time % 3600) / 60);

        if ($hours > 0) {
            return sprintf('%d hr %d min', $hours, $minutes);
        }

        return sprintf('%d min', $minutes);
    }

    public function getRankBadgeAttribute(): string
    {
        return match(true) {
            $this->rank === 1 => '🥇 Champion',
            $this->rank === 2 => '🥈 Runner-up',
            $this->rank === 3 => '🥉 Third Place',
            $this->rank <= 10 => '⭐ Top 10',
            default => '🎓 Achiever',
        };
    }

    public function scopeTopRanked($query, int $limit = 10)
    {
        return $query->orderBy('rank')->limit($limit);
    }

    public static function updateRankings(): void
    {
        // Get all students with completed final assessments
        $students = User::where('role', 'student')
            ->whereHas('assessmentAttempts', function ($query) {
                $query->whereHas('assessment', function ($q) {
                    $q->where('type', 'final');
                })->where('status', 'completed');
            })
            ->get();

        $rankings = [];

        foreach ($students as $student) {
            // Get final assessment score
            $finalAttempt = $student->assessmentAttempts()
                ->whereHas('assessment', function ($query) {
                    $query->where('type', 'final');
                })
                ->where('status', 'completed')
                ->latest()
                ->first();

            if (!$finalAttempt) {
                continue;
            }

            // Calculate activity accuracy
            $totalActivityAttempts = $student->activityAttempts()->count();
            $correctActivityAttempts = $student->activityAttempts()->where('is_correct', true)->count();
            $activityAccuracy = $totalActivityAttempts > 0 
                ? round(($correctActivityAttempts / $totalActivityAttempts) * 100, 2) 
                : 0;

            // Calculate completion rate
            $totalLessons = Lesson::where('is_active', true)->count();
            $completedLessons = $student->progress()
                ->where('status', 'completed')
                ->distinct('lesson_id')
                ->count('lesson_id');
            $completionRate = $totalLessons > 0 
                ? round(($completedLessons / $totalLessons) * 100, 2) 
                : 0;

            // Calculate total learning time
            $totalLearningTime = $student->progress()->sum('time_spent');

            // Calculate improvement percentage
            $initialAttempt = $student->assessmentAttempts()
                ->whereHas('assessment', function ($query) {
                    $query->where('type', 'initial');
                })
                ->where('status', 'completed')
                ->latest()
                ->first();

            $improvementPercentage = 0;
            if ($initialAttempt && $initialAttempt->score > 0) {
                $improvementPercentage = round(
                    (($finalAttempt->score - $initialAttempt->score) / $initialAttempt->score) * 100, 
                    2
                );
            }

            $rankings[] = [
                'user_id' => $student->id,
                'final_score' => $finalAttempt->score,
                'activity_accuracy' => $activityAccuracy,
                'completion_rate' => $completionRate,
                'total_learning_time' => $totalLearningTime,
                'improvement_percentage' => $improvementPercentage,
                // Ranking score calculation (weighted)
                'ranking_score' => ($finalAttempt->score * 0.5) + 
                                  ($activityAccuracy * 0.3) + 
                                  ($completionRate * 0.2),
            ];
        }

        // Sort by ranking score
        usort($rankings, function ($a, $b) {
            return $b['ranking_score'] <=> $a['ranking_score'];
        });

        // Update hall of fame with ranks
        foreach ($rankings as $index => $ranking) {
            HallOfFame::updateOrCreate(
                ['user_id' => $ranking['user_id']],
                [
                    'rank' => $index + 1,
                    'final_score' => $ranking['final_score'],
                    'activity_accuracy' => $ranking['activity_accuracy'],
                    'completion_rate' => $ranking['completion_rate'],
                    'total_learning_time' => $ranking['total_learning_time'],
                    'improvement_percentage' => $ranking['improvement_percentage'],
                ]
            );
        }
    }
}
