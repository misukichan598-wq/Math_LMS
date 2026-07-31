<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityAttempt;
use App\Models\LearningHistory;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    use ApiResponse;

    public function show(Request $request, Activity $activity)
    {
        $user = $request->user();

        $activity->load('lessonSection.lesson');

        $data = [
            'activity' => [
                'id' => $activity->id,
                'title' => $activity->title,
                'instructions' => $activity->instructions,
                'type' => $activity->type,
                'type_label' => $activity->getTypeLabelAttribute(),
                'passing_score' => $activity->passing_score,
                'is_required' => $activity->is_required,
                'total_points' => $activity->getTotalPointsAttribute(),
                'total_questions' => $activity->questions()->count(),
            ],
            'lesson' => [
                'id' => $activity->lessonSection->lesson->id,
                'title' => $activity->lessonSection->lesson->title,
            ],
            'section' => [
                'id' => $activity->lessonSection->id,
                'title' => $activity->lessonSection->title,
            ],
            'student_status' => [
                'is_completed' => $activity->isCompletedBy($user->id),
                'has_passed' => $activity->hasPassedBy($user->id),
                'score' => $activity->getStudentScore($user->id),
                'accuracy' => $activity->getStudentAccuracy($user->id),
            ],
        ];

        return $this->successResponse($data);
    }

    public function questions(Request $request, Activity $activity)
    {
        $user = $request->user();

        $questions = $activity->questions()->ordered()->get()->map(function ($question) use ($user) {
            $attempt = $question->getStudentAnswer($user->id);

            return [
                'id' => $question->id,
                'question' => $question->question,
                'options' => $question->options,
                'points' => $question->points,
                'order' => $question->order,
                'student_answer' => $attempt?->student_answer,
                'is_correct' => $attempt?->is_correct,
                'explanation' => $attempt ? $question->explanation : null, // Only show after attempt
            ];
        });

        return $this->successResponse($questions);
    }

    public function submit(Request $request, Activity $activity)
    {
        $user = $request->user();

        $request->validate([
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'exists:activity_questions,id'],
            'answers.*.answer' => ['required', 'string'],
        ]);

        $results = [];
        $totalPoints = 0;
        $earnedPoints = 0;

        foreach ($request->answers as $answerData) {
            $question = $activity->questions()->find($answerData['question_id']);

            if (!$question) {
                continue;
            }

            $isCorrect = $question->checkAnswer($answerData['answer']);
            $pointsEarned = $isCorrect ? $question->points : 0;

            // Save attempt
            $attempt = ActivityAttempt::create([
                'user_id' => $user->id,
                'activity_id' => $activity->id,
                'activity_question_id' => $question->id,
                'student_answer' => $answerData['answer'],
                'is_correct' => $isCorrect,
                'points_earned' => $pointsEarned,
                'answered_at' => now(),
            ]);

            $totalPoints += $question->points;
            $earnedPoints += $pointsEarned;

            $results[] = [
                'question_id' => $question->id,
                'is_correct' => $isCorrect,
                'points_earned' => $pointsEarned,
                'correct_answer' => $question->correct_answer,
                'explanation' => $question->explanation,
            ];
        }

        $percentage = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 2) : 0;
        $hasPassed = $percentage >= $activity->passing_score;

        // Log completion
        LearningHistory::log(
            $user->id,
            'activity_complete',
            "Completed activity: {$activity->title} with score {$percentage}%",
            $activity,
            [
                'score' => $earnedPoints,
                'total_points' => $totalPoints,
                'percentage' => $percentage,
                'passed' => $hasPassed,
            ]
        );

        return $this->successResponse([
            'results' => $results,
            'summary' => [
                'total_questions' => count($request->answers),
                'total_points' => $totalPoints,
                'earned_points' => $earnedPoints,
                'percentage' => $percentage,
                'has_passed' => $hasPassed,
                'passing_score' => $activity->passing_score,
            ],
        ], 'Activity submitted successfully');
    }

    public function result(Request $request, Activity $activity)
    {
        $user = $request->user();

        $attempts = $activity->attempts()
            ->where('user_id', $user->id)
            ->with('question')
            ->get()
            ->groupBy('activity_question_id')
            ->map(function ($attempts) {
                return $attempts->last(); // Get latest attempt for each question
            });

        $totalPoints = $activity->getTotalPointsAttribute();
        $earnedPoints = $attempts->sum('points_earned');
        $percentage = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 2) : 0;

        $results = $attempts->map(function ($attempt) {
            return [
                'question' => $attempt->question->question,
                'student_answer' => $attempt->student_answer,
                'correct_answer' => $attempt->question->correct_answer,
                'is_correct' => $attempt->is_correct,
                'points_earned' => $attempt->points_earned,
                'max_points' => $attempt->question->points,
                'explanation' => $attempt->question->explanation,
            ];
        })->values();

        return $this->successResponse([
            'activity' => [
                'id' => $activity->id,
                'title' => $activity->title,
                'type' => $activity->type,
            ],
            'results' => $results,
            'summary' => [
                'total_questions' => $attempts->count(),
                'correct_answers' => $attempts->where('is_correct', true)->count(),
                'total_points' => $totalPoints,
                'earned_points' => $earnedPoints,
                'percentage' => $percentage,
                'has_passed' => $percentage >= $activity->passing_score,
                'passing_score' => $activity->passing_score,
            ],
        ]);
    }
}
