<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonSection;
use App\Models\LearningHistory;
use App\Models\StudentProgress;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = $request->user();
        
        $lessons = Lesson::active()
            ->ordered()
            ->get()
            ->map(function ($lesson) use ($user) {
                $progress = $lesson->getStudentProgress($user->id);
                
                return [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'description' => $lesson->description,
                    'order' => $lesson->order,
                    'estimated_duration' => $lesson->estimated_duration,
                    'total_sections' => $lesson->getTotalSectionsAttribute(),
                    'total_activities' => $lesson->getTotalActivitiesAttribute(),
                    'has_pdf' => !is_null($lesson->pdf_path),
                    'status' => $progress?->status ?? 'not_started',
                    'progress_percentage' => $lesson->getCompletionPercentage($user->id),
                    'started_at' => $progress?->started_at,
                    'completed_at' => $progress?->completed_at,
                ];
            });

        return $this->successResponse($lessons);
    }

    public function show(Request $request, Lesson $lesson)
    {
        $user = $request->user();
        
        $lesson->load('sections');
        $progress = $lesson->getStudentProgress($user->id);

        $sections = $lesson->sections->map(function ($section) use ($user) {
            $sectionProgress = StudentProgress::where('user_id', $user->id)
                ->where('lesson_section_id', $section->id)
                ->first();

            return [
                'id' => $section->id,
                'title' => $section->title,
                'order' => $section->order,
                'has_activity' => $section->has_activity,
                'is_required' => $section->is_required,
                'status' => $sectionProgress?->status ?? 'not_started',
                'completed_at' => $sectionProgress?->completed_at,
            ];
        });

        $data = [
            'lesson' => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'description' => $lesson->description,
                'pdf_url' => $lesson->getPdfUrlAttribute(),
                'estimated_duration' => $lesson->estimated_duration,
                'status' => $progress?->status ?? 'not_started',
                'progress_percentage' => $lesson->getCompletionPercentage($user->id),
                'started_at' => $progress?->started_at,
                'completed_at' => $progress?->completed_at,
            ],
            'sections' => $sections,
        ];

        return $this->successResponse($data);
    }

    public function sections(Request $request, Lesson $lesson)
    {
        $user = $request->user();
        
        $sections = $lesson->sections()->ordered()->get()->map(function ($section) use ($user) {
            $sectionProgress = StudentProgress::where('user_id', $user->id)
                ->where('lesson_section_id', $section->id)
                ->first();

            return [
                'id' => $section->id,
                'title' => $section->title,
                'order' => $section->order,
                'has_activity' => $section->has_activity,
                'is_required' => $section->is_required,
                'status' => $sectionProgress?->status ?? 'not_started',
                'completed_at' => $sectionProgress?->completed_at,
            ];
        });

        return $this->successResponse($sections);
    }

    public function showSection(Request $request, Lesson $lesson, LessonSection $section)
    {
        $user = $request->user();

        // Check if section belongs to lesson
        if ($section->lesson_id !== $lesson->id) {
            return $this->notFoundResponse('Section not found in this lesson');
        }

        $sectionProgress = StudentProgress::where('user_id', $user->id)
            ->where('lesson_section_id', $section->id)
            ->first();

        $activities = $section->activities()->ordered()->get()->map(function ($activity) use ($user) {
            return [
                'id' => $activity->id,
                'title' => $activity->title,
                'type' => $activity->type,
                'type_label' => $activity->getTypeLabelAttribute(),
                'instructions' => $activity->instructions,
                'is_required' => $activity->is_required,
                'passing_score' => $activity->passing_score,
                'total_points' => $activity->getTotalPointsAttribute(),
                'is_completed' => $activity->isCompletedBy($user->id),
                'has_passed' => $activity->hasPassedBy($user->id),
            ];
        });

        $data = [
            'section' => [
                'id' => $section->id,
                'title' => $section->title,
                'content' => $section->content,
                'order' => $section->order,
                'has_activity' => $section->has_activity,
                'is_required' => $section->is_required,
                'status' => $sectionProgress?->status ?? 'not_started',
            ],
            'activities' => $activities,
            'navigation' => [
                'previous' => $section->getPreviousSection()?->id,
                'next' => $section->getNextSection()?->id,
            ],
        ];

        // Mark section as started if not already
        if (!$sectionProgress) {
            StudentProgress::create([
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
                'lesson_section_id' => $section->id,
                'status' => 'in_progress',
                'started_at' => now(),
            ]);

            LearningHistory::log(
                $user->id,
                'section_start',
                "Started section: {$section->title}",
                $section
            );
        }

        return $this->successResponse($data);
    }

    public function start(Request $request, Lesson $lesson)
    {
        $user = $request->user();

        // Check if already started
        $progress = StudentProgress::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->whereNull('lesson_section_id')
            ->first();

        if (!$progress) {
            $progress = StudentProgress::create([
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
                'status' => 'in_progress',
                'started_at' => now(),
            ]);

            LearningHistory::log(
                $user->id,
                'lesson_start',
                "Started lesson: {$lesson->title}",
                $lesson
            );
        }

        return $this->successResponse($progress, 'Lesson started successfully');
    }

    public function completeSection(Request $request, Lesson $lesson, LessonSection $section)
    {
        $user = $request->user();

        // Check if section belongs to lesson
        if ($section->lesson_id !== $lesson->id) {
            return $this->notFoundResponse('Section not found in this lesson');
        }

        // Check if section has required activities and they are completed
        if ($section->has_activity) {
            $requiredActivities = $section->activities()->where('is_required', true)->get();
            
            foreach ($requiredActivities as $activity) {
                if (!$activity->hasPassedBy($user->id)) {
                    return $this->errorResponse(
                        'You must complete and pass all required activities before completing this section',
                        400
                    );
                }
            }
        }

        $sectionProgress = StudentProgress::updateOrCreate(
            [
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
                'lesson_section_id' => $section->id,
            ],
            [
                'status' => 'completed',
                'progress_percentage' => 100,
                'completed_at' => now(),
            ]
        );

        LearningHistory::log(
            $user->id,
            'section_complete',
            "Completed section: {$section->title}",
            $section
        );

        // Check if all sections are completed
        $totalSections = $lesson->sections()->count();
        $completedSections = StudentProgress::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->whereNotNull('lesson_section_id')
            ->where('status', 'completed')
            ->count();

        if ($totalSections === $completedSections) {
            // Mark lesson as completed
            StudentProgress::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'lesson_id' => $lesson->id,
                    'lesson_section_id' => null,
                ],
                [
                    'status' => 'completed',
                    'progress_percentage' => 100,
                    'completed_at' => now(),
                ]
            );

            LearningHistory::log(
                $user->id,
                'lesson_complete',
                "Completed lesson: {$lesson->title}",
                $lesson
            );

            return $this->successResponse($sectionProgress, 'Section and lesson completed successfully!');
        }

        return $this->successResponse($sectionProgress, 'Section completed successfully');
    }

    public function downloadPdf(Request $request, Lesson $lesson)
    {
        if (!$lesson->pdf_path) {
            return $this->notFoundResponse('PDF not available for this lesson');
        }

        $path = storage_path('app/public/' . $lesson->pdf_path);

        if (!file_exists($path)) {
            return $this->notFoundResponse('PDF file not found');
        }

        return response()->download($path, $lesson->title . '.pdf');
    }
}
