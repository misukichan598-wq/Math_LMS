<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LearningHistory;
use App\Models\StudentProgress;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = $request->user();

        $progress = StudentProgress::where('user_id', $user->id)
            ->with(['lesson', 'lessonSection'])
            ->latest()
            ->paginate(20);

        return $this->successResponse($progress);
    }

    public function overview(Request $request)
    {
        $user = $request->user();

        $totalLessons = Lesson::active()->count();
        $completedLessons = StudentProgress::where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereNull('lesson_section_id')
            ->count();

        $inProgressLessons = StudentProgress::where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->whereNull('lesson_section_id')
            ->count();

        $totalLearningTime = StudentProgress::where('user_id', $user->id)
            ->sum('time_spent');

        $completedActivities = $user->activityAttempts()
            ->distinct('activity_id')
            ->count('activity_id');

        $overallProgress = $totalLessons > 0 
            ? round(($completedLessons / $totalLessons) * 100, 2) 
            : 0;

        return $this->successResponse([
            'total_lessons' => $totalLessons,
            'completed_lessons' => $completedLessons,
            'in_progress_lessons' => $inProgressLessons,
            'overall_progress' => $overallProgress,
            'total_learning_time' => $totalLearningTime,
            'total_learning_time_formatted' => $this->formatTime($totalLearningTime),
            'completed_activities' => $completedActivities,
            'completed_initial_assessment' => $user->hasCompletedInitialAssessment(),
            'completed_final_assessment' => $user->hasCompletedFinalAssessment(),
        ]);
    }

    public function lessonProgress(Request $request)
    {
        $user = $request->user();

        $lessons = Lesson::active()->ordered()->get()->map(function ($lesson) use ($user) {
            $progress = $lesson->getStudentProgress($user->id);
            $completionPercentage = $lesson->getCompletionPercentage($user->id);

            return [
                'lesson_id' => $lesson->id,
                'lesson_title' => $lesson->title,
                'status' => $progress?->status ?? 'not_started',
                'progress_percentage' => $completionPercentage,
                'time_spent' => $progress?->time_spent ?? 0,
                'time_spent_formatted' => $this->formatTime($progress?->time_spent ?? 0),
                'started_at' => $progress?->started_at,
                'completed_at' => $progress?->completed_at,
            ];
        });

        return $this->successResponse($lessons);
    }

    public function lessonDetail(Request $request, Lesson $lesson)
    {
        $user = $request->user();

        $sections = $lesson->sections()->ordered()->get()->map(function ($section) use ($user) {
            $sectionProgress = StudentProgress::where('user_id', $user->id)
                ->where('lesson_section_id', $section->id)
                ->first();

            $activities = $section->activities()->ordered()->get()->map(function ($activity) use ($user) {
                return [
                    'id' => $activity->id,
                    'title' => $activity->title,
                    'type' => $activity->type,
                    'is_completed' => $activity->isCompletedBy($user->id),
                    'has_passed' => $activity->hasPassedBy($user->id),
                    'score' => $activity->getStudentScore($user->id),
                    'accuracy' => $activity->getStudentAccuracy($user->id),
                ];
            });

            return [
                'id' => $section->id,
                'title' => $section->title,
                'status' => $sectionProgress?->status ?? 'not_started',
                'completed_at' => $sectionProgress?->completed_at,
                'activities' => $activities,
            ];
        });

        $lessonProgress = $lesson->getStudentProgress($user->id);

        return $this->successResponse([
            'lesson' => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'description' => $lesson->description,
            ],
            'progress' => [
                'status' => $lessonProgress?->status ?? 'not_started',
                'progress_percentage' => $lesson->getCompletionPercentage($user->id),
                'time_spent' => $lessonProgress?->time_spent ?? 0,
                'time_spent_formatted' => $this->formatTime($lessonProgress?->time_spent ?? 0),
                'started_at' => $lessonProgress?->started_at,
                'completed_at' => $lessonProgress?->completed_at,
            ],
            'sections' => $sections,
        ]);
    }

    public function trackTime(Request $request)
    {
        $request->validate([
            'lesson_id' => ['required', 'exists:lessons,id'],
            'lesson_section_id' => ['nullable', 'exists:lesson_sections,id'],
            'seconds' => ['required', 'integer', 'min:1'],
        ]);

        $user = $request->user();

        $progress = StudentProgress::where('user_id', $user->id)
            ->where('lesson_id', $request->lesson_id)
            ->where('lesson_section_id', $request->lesson_section_id)
            ->first();

        if ($progress) {
            $progress->addTimeSpent($request->seconds);
        }

        return $this->successResponse(null, 'Time tracked successfully');
    }

    public function scores(Request $request)
    {
        $user = $request->user();

        $scores = $user->scores()->with('lesson')->latest()->get()->map(function ($score) {
            return [
                'id' => $score->id,
                'score_type' => $score->score_type,
                'score_type_label' => $score->getScoreTypeLabelAttribute(),
                'score' => $score->score,
                'max_score' => $score->max_score,
                'percentage' => $score->percentage,
                'grade' => $score->getGradeAttribute(),
                'lesson' => $score->lesson ? [
                    'id' => $score->lesson->id,
                    'title' => $score->lesson->title,
                ] : null,
                'created_at' => $score->created_at,
            ];
        });

        return $this->successResponse($scores);
    }

    public function scoresSummary(Request $request)
    {
        $user = $request->user();

        $initialAssessment = $user->assessmentAttempts()
            ->whereHas('assessment', fn($q) => $q->where('type', 'initial'))
            ->where('status', 'completed')
            ->latest()
            ->first();

        $finalAssessment = $user->assessmentAttempts()
            ->whereHas('assessment', fn($q) => $q->where('type', 'final'))
            ->where('status', 'completed')
            ->latest()
            ->first();

        $activityScores = $user->activityAttempts()
            ->where('is_correct', true)
            ->count();

        $totalActivityAttempts = $user->activityAttempts()->count();
        
        $activityAccuracy = $totalActivityAttempts > 0 
            ? round(($activityScores / $totalActivityAttempts) * 100, 2) 
            : 0;

        return $this->successResponse([
            'initial_assessment' => $initialAssessment ? [
                'score' => $initialAssessment->score,
                'percentage' => $initialAssessment->getPercentageAttribute(),
                'completed_at' => $initialAssessment->completed_at,
            ] : null,
            'final_assessment' => $finalAssessment ? [
                'score' => $finalAssessment->score,
                'percentage' => $finalAssessment->getPercentageAttribute(),
                'completed_at' => $finalAssessment->completed_at,
            ] : null,
            'activity_accuracy' => $activityAccuracy,
            'total_activity_attempts' => $totalActivityAttempts,
            'correct_activity_attempts' => $activityScores,
        ]);
    }

    public function assessmentComparison(Request $request)
    {
        $user = $request->user();

        $initialAttempt = $user->assessmentAttempts()
            ->whereHas('assessment', fn($q) => $q->where('type', 'initial'))
            ->where('status', 'completed')
            ->latest()
            ->first();

        $finalAttempt = $user->assessmentAttempts()
            ->whereHas('assessment', fn($q) => $q->where('type', 'final'))
            ->where('status', 'completed')
            ->latest()
            ->first();

        if (!$initialAttempt || !$finalAttempt) {
            return $this->errorResponse('Both assessments must be completed for comparison', 400);
        }

        $improvement = $finalAttempt->score - $initialAttempt->score;
        $improvementPercentage = $initialAttempt->score > 0 
            ? round(($improvement / $initialAttempt->score) * 100, 2) 
            : 0;

        return $this->successResponse([
            'initial_assessment' => [
                'score' => $initialAttempt->score,
                'correct_answers' => $initialAttempt->correct_answers,
                'total_questions' => $initialAttempt->total_questions,
                'percentage' => $initialAttempt->getPercentageAttribute(),
                'completed_at' => $initialAttempt->completed_at,
            ],
            'final_assessment' => [
                'score' => $finalAttempt->score,
                'correct_answers' => $finalAttempt->correct_answers,
                'total_questions' => $finalAttempt->total_questions,
                'percentage' => $finalAttempt->getPercentageAttribute(),
                'completed_at' => $finalAttempt->completed_at,
            ],
            'improvement' => [
                'score_difference' => $improvement,
                'improvement_percentage' => $improvementPercentage,
                'improved' => $improvement > 0,
            ],
        ]);
    }

    public function history(Request $request)
    {
        $user = $request->user();

        $history = LearningHistory::where('user_id', $user->id)
            ->latest()
            ->paginate(50);

        return $this->successResponse($history);
    }

    public function recentHistory(Request $request)
    {
        $user = $request->user();

        $history = LearningHistory::where('user_id', $user->id)
            ->recent(20)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'activity_type' => $item->activity_type,
                    'activity_description' => $item->activity_description,
                    'icon' => $item->getActivityTypeIconAttribute(),
                    'color' => $item->getActivityTypeColorAttribute(),
                    'metadata' => $item->metadata,
                    'created_at' => $item->created_at,
                    'created_at_human' => $item->created_at->diffForHumans(),
                ];
            });

        return $this->successResponse($history);
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();

        // Overall stats
        $totalLessons = Lesson::active()->count();
        $completedLessons = StudentProgress::where('user_id', $user->id)
            ->where('status', 'completed')
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

        // Recent activity
        $recentHistory = LearningHistory::where('user_id', $user->id)
            ->recent(5)
            ->get();

        // Unread notifications
        $unreadNotifications = $user->notifications()->unread()->count();

        // Hall of Fame rank
        $hallOfFame = $user->hallOfFame;

        // Assessment status
        $completedInitial = $user->hasCompletedInitialAssessment();
        $completedFinal = $user->hasCompletedFinalAssessment();

        return $this->successResponse([
            'stats' => [
                'overall_progress' => $overallProgress,
                'completed_lessons' => $completedLessons,
                'total_lessons' => $totalLessons,
                'completed_initial_assessment' => $completedInitial,
                'completed_final_assessment' => $completedFinal,
            ],
            'current_lesson' => $currentLesson ? [
                'id' => $currentLesson->lesson->id,
                'title' => $currentLesson->lesson->title,
                'progress_percentage' => $currentLesson->lesson->getCompletionPercentage($user->id),
            ] : null,
            'recent_activity' => $recentHistory,
            'unread_notifications' => $unreadNotifications,
            'hall_of_fame_rank' => $hallOfFame?->rank,
        ]);
    }

    private function formatTime(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' sec';
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return sprintf('%d hr %d min', $hours, $minutes);
        }

        return sprintf('%d min', $minutes);
    }
}
