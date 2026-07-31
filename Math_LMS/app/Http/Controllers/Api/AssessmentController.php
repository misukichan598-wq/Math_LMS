<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentAttempt;
use App\Models\HallOfFame;
use App\Models\LearningHistory;
use App\Models\Notification;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    use ApiResponse;

    public function getInitial(Request $request)
    {
        $user = $request->user();
        $assessment = Assessment::initial()->active()->first();

        if (!$assessment) {
            return $this->notFoundResponse('Initial assessment not available');
        }

        $hasCompleted = $assessment->hasBeenCompletedBy($user->id);
        $attempt = $assessment->getStudentAttempt($user->id);

        return $this->successResponse([
            'assessment' => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'description' => $assessment->description,
                'type' => $assessment->type,
                'total_questions' => $assessment->getTotalQuestionsAttribute(),
                'time_limit' => $assessment->time_limit,
                'passing_score' => $assessment->passing_score,
            ],
            'status' => [
                'has_completed' => $hasCompleted,
                'score' => $attempt?->score,
                'percentage' => $attempt?->getPercentageAttribute(),
                'completed_at' => $attempt?->completed_at,
            ],
        ]);
    }

    public function startInitial(Request $request)
    {
        $user = $request->user();
        $assessment = Assessment::initial()->active()->first();

        if (!$assessment) {
            return $this->notFoundResponse('Initial assessment not available');
        }

        // Check if already completed
        if ($assessment->hasBeenCompletedBy($user->id)) {
            return $this->errorResponse('You have already completed the initial assessment', 400);
        }

        // Check for in-progress attempt
        $inProgressAttempt = AssessmentAttempt::where('user_id', $user->id)
            ->where('assessment_id', $assessment->id)
            ->where('status', 'in_progress')
            ->first();

        if ($inProgressAttempt) {
            return $this->successResponse($inProgressAttempt, 'Resuming existing attempt');
        }

        // Create new attempt
        $attempt = AssessmentAttempt::create([
            'user_id' => $user->id,
            'assessment_id' => $assessment->id,
            'total_questions' => $assessment->getTotalQuestionsAttribute(),
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        LearningHistory::log(
            $user->id,
            'assessment_start',
            "Started initial assessment",
            $assessment
        );

        return $this->successResponse($attempt, 'Assessment started successfully');
    }

    public function getInitialQuestions(Request $request, AssessmentAttempt $attempt)
    {
        $user = $request->user();

        // Check ownership
        if ($attempt->user_id !== $user->id) {
            return $this->forbiddenResponse();
        }

        // Check if it's an initial assessment
        if ($attempt->assessment->type !== 'initial') {
            return $this->errorResponse('This is not an initial assessment', 400);
        }

        $questions = $attempt->assessment->questions()->ordered()->get()->map(function ($question) use ($attempt) {
            $answer = $question->getStudentAnswer($attempt->id);

            return [
                'id' => $question->id,
                'question' => $question->question,
                'options' => $question->options,
                'points' => $question->points,
                'order' => $question->order,
                'student_answer' => $answer?->student_answer,
            ];
        });

        return $this->successResponse([
            'attempt_id' => $attempt->id,
            'questions' => $questions,
            'time_limit' => $attempt->assessment->time_limit,
            'started_at' => $attempt->started_at,
        ]);
    }

    public function answerInitialQuestion(Request $request, AssessmentAttempt $attempt)
    {
        $user = $request->user();

        // Check ownership
        if ($attempt->user_id !== $user->id) {
            return $this->forbiddenResponse();
        }

        // Check if attempt is still in progress
        if ($attempt->status !== 'in_progress') {
            return $this->errorResponse('This assessment attempt has already been completed', 400);
        }

        $request->validate([
            'question_id' => ['required', 'exists:assessment_questions,id'],
            'answer' => ['required', 'string'],
        ]);

        $question = $attempt->assessment->questions()->find($request->question_id);

        if (!$question) {
            return $this->notFoundResponse('Question not found in this assessment');
        }

        $isCorrect = $question->checkAnswer($request->answer);
        $pointsEarned = $isCorrect ? $question->points : 0;

        // Save or update answer
        $answer = AssessmentAnswer::updateOrCreate(
            [
                'assessment_attempt_id' => $attempt->id,
                'assessment_question_id' => $question->id,
            ],
            [
                'student_answer' => $request->answer,
                'is_correct' => $isCorrect,
                'points_earned' => $pointsEarned,
            ]
        );

        return $this->successResponse($answer, 'Answer saved successfully');
    }

    public function submitInitial(Request $request, AssessmentAttempt $attempt)
    {
        $user = $request->user();

        // Check ownership
        if ($attempt->user_id !== $user->id) {
            return $this->forbiddenResponse();
        }

        // Check if attempt is still in progress
        if ($attempt->status !== 'in_progress') {
            return $this->errorResponse('This assessment attempt has already been completed', 400);
        }

        // Calculate score
        $attempt->calculateScore();

        // Calculate time taken
        $timeTaken = now()->diffInSeconds($attempt->started_at);

        // Update attempt
        $attempt->update([
            'status' => 'completed',
            'completed_at' => now(),
            'time_taken' => $timeTaken,
        ]);

        LearningHistory::log(
            $user->id,
            'assessment_complete',
            "Completed initial assessment with score {$attempt->score}%",
            $attempt->assessment,
            ['score' => $attempt->score, 'percentage' => $attempt->getPercentageAttribute()]
        );

        // Create notification
        Notification::createForUser(
            $user->id,
            'Assessment Completed',
            "You completed the initial assessment with a score of {$attempt->score}%. You can now begin learning!",
            'assessment',
            route('student.assessment.result', $attempt->id)
        );

        return $this->successResponse([
            'attempt' => $attempt->fresh(),
            'result' => [
                'score' => $attempt->score,
                'percentage' => $attempt->getPercentageAttribute(),
                'correct_answers' => $attempt->correct_answers,
                'total_questions' => $attempt->total_questions,
                'has_passed' => $attempt->hasPassed(),
                'time_taken' => $attempt->getTimeTakenFormattedAttribute(),
            ],
        ], 'Assessment submitted successfully');
    }

    public function getFinal(Request $request)
    {
        $user = $request->user();

        // Check if initial assessment is completed
        if (!$user->hasCompletedInitialAssessment()) {
            return $this->errorResponse('You must complete the initial assessment first', 400);
        }

        $assessment = Assessment::final()->active()->first();

        if (!$assessment) {
            return $this->notFoundResponse('Final assessment not available');
        }

        $hasCompleted = $assessment->hasBeenCompletedBy($user->id);
        $attempt = $assessment->getStudentAttempt($user->id);

        return $this->successResponse([
            'assessment' => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'description' => $assessment->description,
                'type' => $assessment->type,
                'total_questions' => $assessment->getTotalQuestionsAttribute(),
                'time_limit' => $assessment->time_limit,
                'passing_score' => $assessment->passing_score,
            ],
            'status' => [
                'has_completed' => $hasCompleted,
                'score' => $attempt?->score,
                'percentage' => $attempt?->getPercentageAttribute(),
                'completed_at' => $attempt?->completed_at,
            ],
        ]);
    }

    public function startFinal(Request $request)
    {
        $user = $request->user();

        // Check if initial assessment is completed
        if (!$user->hasCompletedInitialAssessment()) {
            return $this->errorResponse('You must complete the initial assessment first', 400);
        }

        $assessment = Assessment::final()->active()->first();

        if (!$assessment) {
            return $this->notFoundResponse('Final assessment not available');
        }

        // Check if already completed
        if ($assessment->hasBeenCompletedBy($user->id)) {
            return $this->errorResponse('You have already completed the final assessment', 400);
        }

        // Check for in-progress attempt
        $inProgressAttempt = AssessmentAttempt::where('user_id', $user->id)
            ->where('assessment_id', $assessment->id)
            ->where('status', 'in_progress')
            ->first();

        if ($inProgressAttempt) {
            return $this->successResponse($inProgressAttempt, 'Resuming existing attempt');
        }

        // Create new attempt
        $attempt = AssessmentAttempt::create([
            'user_id' => $user->id,
            'assessment_id' => $assessment->id,
            'total_questions' => $assessment->getTotalQuestionsAttribute(),
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        LearningHistory::log(
            $user->id,
            'assessment_start',
            "Started final assessment",
            $assessment
        );

        return $this->successResponse($attempt, 'Assessment started successfully');
    }

    public function getFinalQuestions(Request $request, AssessmentAttempt $attempt)
    {
        $user = $request->user();

        // Check ownership
        if ($attempt->user_id !== $user->id) {
            return $this->forbiddenResponse();
        }

        // Check if it's a final assessment
        if ($attempt->assessment->type !== 'final') {
            return $this->errorResponse('This is not a final assessment', 400);
        }

        $questions = $attempt->assessment->questions()->ordered()->get()->map(function ($question) use ($attempt) {
            $answer = $question->getStudentAnswer($attempt->id);

            return [
                'id' => $question->id,
                'question' => $question->question,
                'options' => $question->options,
                'points' => $question->points,
                'order' => $question->order,
                'student_answer' => $answer?->student_answer,
            ];
        });

        return $this->successResponse([
            'attempt_id' => $attempt->id,
            'questions' => $questions,
            'time_limit' => $attempt->assessment->time_limit,
            'started_at' => $attempt->started_at,
        ]);
    }

    public function answerFinalQuestion(Request $request, AssessmentAttempt $attempt)
    {
        return $this->answerInitialQuestion($request, $attempt);
    }

    public function submitFinal(Request $request, AssessmentAttempt $attempt)
    {
        $user = $request->user();

        // Check ownership
        if ($attempt->user_id !== $user->id) {
            return $this->forbiddenResponse();
        }

        // Check if attempt is still in progress
        if ($attempt->status !== 'in_progress') {
            return $this->errorResponse('This assessment attempt has already been completed', 400);
        }

        // Calculate score
        $attempt->calculateScore();

        // Calculate time taken
        $timeTaken = now()->diffInSeconds($attempt->started_at);

        // Update attempt
        $attempt->update([
            'status' => 'completed',
            'completed_at' => now(),
            'time_taken' => $timeTaken,
        ]);

        LearningHistory::log(
            $user->id,
            'assessment_complete',
            "Completed final assessment with score {$attempt->score}%",
            $attempt->assessment,
            ['score' => $attempt->score, 'percentage' => $attempt->getPercentageAttribute()]
        );

        // Update Hall of Fame rankings
        HallOfFame::updateRankings();

        // Create notification
        Notification::createForUser(
            $user->id,
            'Final Assessment Completed',
            "Congratulations! You completed the final assessment with a score of {$attempt->score}%. Check the Hall of Fame!",
            'achievement',
            route('student.hall-of-fame.index')
        );

        return $this->successResponse([
            'attempt' => $attempt->fresh(),
            'result' => [
                'score' => $attempt->score,
                'percentage' => $attempt->getPercentageAttribute(),
                'correct_answers' => $attempt->correct_answers,
                'total_questions' => $attempt->total_questions,
                'has_passed' => $attempt->hasPassed(),
                'time_taken' => $attempt->getTimeTakenFormattedAttribute(),
            ],
        ], 'Final assessment submitted successfully! Hall of Fame rankings updated.');
    }

    public function getAttempt(Request $request, AssessmentAttempt $attempt)
    {
        $user = $request->user();

        // Check ownership
        if ($attempt->user_id !== $user->id) {
            return $this->forbiddenResponse();
        }

        return $this->successResponse($attempt->load('assessment'));
    }

    public function getResult(Request $request, AssessmentAttempt $attempt)
    {
        $user = $request->user();

        // Check ownership
        if ($attempt->user_id !== $user->id) {
            return $this->forbiddenResponse();
        }

        // Check if completed
        if ($attempt->status !== 'completed') {
            return $this->errorResponse('This assessment has not been completed yet', 400);
        }

        $answers = $attempt->answers()->with('question')->get()->map(function ($answer) {
            return [
                'question' => $answer->question->question,
                'options' => $answer->question->options,
                'student_answer' => $answer->student_answer,
                'correct_answer' => $answer->question->correct_answer,
                'is_correct' => $answer->is_correct,
                'points_earned' => $answer->points_earned,
                'max_points' => $answer->question->points,
                'explanation' => $answer->question->explanation,
            ];
        });

        // Get initial assessment score for comparison if this is final
        $comparison = null;
        if ($attempt->assessment->type === 'final') {
            $initialAttempt = AssessmentAttempt::where('user_id', $user->id)
                ->whereHas('assessment', function ($query) {
                    $query->where('type', 'initial');
                })
                ->where('status', 'completed')
                ->latest()
                ->first();

            if ($initialAttempt) {
                $improvement = $attempt->score - $initialAttempt->score;
                $improvementPercentage = $initialAttempt->score > 0 
                    ? round(($improvement / $initialAttempt->score) * 100, 2) 
                    : 0;

                $comparison = [
                    'initial_score' => $initialAttempt->score,
                    'final_score' => $attempt->score,
                    'improvement' => $improvement,
                    'improvement_percentage' => $improvementPercentage,
                ];
            }
        }

        return $this->successResponse([
            'assessment' => [
                'id' => $attempt->assessment->id,
                'title' => $attempt->assessment->title,
                'type' => $attempt->assessment->type,
            ],
            'attempt' => [
                'id' => $attempt->id,
                'score' => $attempt->score,
                'percentage' => $attempt->getPercentageAttribute(),
                'correct_answers' => $attempt->correct_answers,
                'total_questions' => $attempt->total_questions,
                'has_passed' => $attempt->hasPassed(),
                'time_taken' => $attempt->getTimeTakenFormattedAttribute(),
                'completed_at' => $attempt->completed_at,
            ],
            'answers' => $answers,
            'comparison' => $comparison,
        ]);
    }
}
