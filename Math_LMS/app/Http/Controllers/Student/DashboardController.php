<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Lesson;
use App\Models\LearningHistory;
use App\Models\StudentProgress;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Overall stats
        $totalLessons = Lesson::active()->count();
        $completedLessons = StudentProgress::where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereNull('lesson_section_id')
            ->distinct('lesson_id')
            ->count('lesson_id');

        $inProgressLessons = StudentProgress::where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->whereNull('lesson_section_id')
            ->count();

        $overallProgress = $totalLessons > 0 
            ? round(($completedLessons / $totalLessons) * 100, 2) 
            : 0;

        // Current lesson
        $currentLesson = StudentProgress::where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->whereNull('lesson_section_id')
            ->with('lesson')
            ->latest('updated_at')
            ->first();

        // Assessment status
        $completedInitial = $user->hasCompletedInitialAssessment();
        $completedFinal = $user->hasCompletedFinalAssessment();

        // Get initial and final assessment scores
        $initialScore = null;
        $finalScore = null;

        if ($completedInitial) {
            $initialAttempt = $user->assessmentAttempts()
                ->whereHas('assessment', fn($q) => $q->where('type', 'initial'))
                ->where('status', 'completed')
                ->latest()
                ->first();
            $initialScore = $initialAttempt?->score;
        }

        if ($completedFinal) {
            $finalAttempt = $user->assessmentAttempts()
                ->whereHas('assessment', fn($q) => $q->where('type', 'final'))
                ->where('status', 'completed')
                ->latest()
                ->first();
            $finalScore = $finalAttempt?->score;
        }

        // Recent learning history
        $recentHistory = LearningHistory::where('user_id', $user->id)
            ->recent(10)
            ->get();

        // Recent announcements
        $announcements = Announcement::published()
            ->latest('published_at')
            ->limit(3)
            ->get();

        // Unread notifications
        $unreadNotifications = $user->notifications()->unread()->count();

        // Hall of Fame rank
        $hallOfFame = $user->hallOfFame;

        // Total learning time
        $totalLearningTime = StudentProgress::where('user_id', $user->id)
            ->sum('time_spent');

        return view('student.dashboard', compact(
            'user',
            'totalLessons',
            'completedLessons',
            'inProgressLessons',
            'overallProgress',
            'currentLesson',
            'completedInitial',
            'completedFinal',
            'initialScore',
            'finalScore',
            'recentHistory',
            'announcements',
            'unreadNotifications',
            'hallOfFame',
            'totalLearningTime'
        ));
    }
}
