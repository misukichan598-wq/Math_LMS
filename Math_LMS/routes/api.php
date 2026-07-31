<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AssessmentController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\HallOfFameController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\AnnouncementController;

Route::prefix('v1')->group(function () {
    
    // Public routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
        
        // Profile
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::post('/profile/picture', [ProfileController::class, 'updatePicture']);
        Route::post('/change-password', [ProfileController::class, 'changePassword']);
        
        // Lessons
        Route::get('/lessons', [LessonController::class, 'index']);
        Route::get('/lessons/{lesson}', [LessonController::class, 'show']);
        Route::get('/lessons/{lesson}/sections', [LessonController::class, 'sections']);
        Route::get('/lessons/{lesson}/sections/{section}', [LessonController::class, 'showSection']);
        Route::post('/lessons/{lesson}/start', [LessonController::class, 'start']);
        Route::post('/lessons/{lesson}/sections/{section}/complete', [LessonController::class, 'completeSection']);
        Route::get('/lessons/{lesson}/pdf', [LessonController::class, 'downloadPdf']);
        
        // Activities
        Route::get('/activities/{activity}', [ActivityController::class, 'show']);
        Route::get('/activities/{activity}/questions', [ActivityController::class, 'questions']);
        Route::post('/activities/{activity}/submit', [ActivityController::class, 'submit']);
        Route::get('/activities/{activity}/result', [ActivityController::class, 'result']);
        
        // Assessments
        Route::get('/assessments/initial', [AssessmentController::class, 'getInitial']);
        Route::post('/assessments/initial/start', [AssessmentController::class, 'startInitial']);
        Route::get('/assessments/initial/questions/{attempt}', [AssessmentController::class, 'getInitialQuestions']);
        Route::post('/assessments/initial/answer/{attempt}', [AssessmentController::class, 'answerInitialQuestion']);
        Route::post('/assessments/initial/submit/{attempt}', [AssessmentController::class, 'submitInitial']);
        
        Route::get('/assessments/final', [AssessmentController::class, 'getFinal']);
        Route::post('/assessments/final/start', [AssessmentController::class, 'startFinal']);
        Route::get('/assessments/final/questions/{attempt}', [AssessmentController::class, 'getFinalQuestions']);
        Route::post('/assessments/final/answer/{attempt}', [AssessmentController::class, 'answerFinalQuestion']);
        Route::post('/assessments/final/submit/{attempt}', [AssessmentController::class, 'submitFinal']);
        
        Route::get('/assessments/attempts/{attempt}', [AssessmentController::class, 'getAttempt']);
        Route::get('/assessments/attempts/{attempt}/result', [AssessmentController::class, 'getResult']);
        
        // Progress
        Route::get('/progress', [ProgressController::class, 'index']);
        Route::get('/progress/overview', [ProgressController::class, 'overview']);
        Route::get('/progress/lessons', [ProgressController::class, 'lessonProgress']);
        Route::get('/progress/lessons/{lesson}', [ProgressController::class, 'lessonDetail']);
        Route::post('/progress/track', [ProgressController::class, 'trackTime']);
        
        // Scores
        Route::get('/scores', [ProgressController::class, 'scores']);
        Route::get('/scores/summary', [ProgressController::class, 'scoresSummary']);
        Route::get('/scores/comparison', [ProgressController::class, 'assessmentComparison']);
        
        // Learning History
        Route::get('/history', [ProgressController::class, 'history']);
        Route::get('/history/recent', [ProgressController::class, 'recentHistory']);
        
        // Hall of Fame
        Route::get('/hall-of-fame', [HallOfFameController::class, 'index']);
        Route::get('/hall-of-fame/top', [HallOfFameController::class, 'topRanked']);
        Route::get('/hall-of-fame/my-rank', [HallOfFameController::class, 'myRank']);
        
        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread', [NotificationController::class, 'unread']);
        Route::get('/notifications/unread/count', [NotificationController::class, 'unreadCount']);
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/notifications/{notification}', [NotificationController::class, 'delete']);
        
        // Announcements
        Route::get('/announcements', [AnnouncementController::class, 'index']);
        Route::get('/announcements/latest', [AnnouncementController::class, 'latest']);
        Route::get('/announcements/{announcement}', [AnnouncementController::class, 'show']);
        
        // Bookmarks
        Route::get('/bookmarks', [ProfileController::class, 'bookmarks']);
        Route::post('/bookmarks', [ProfileController::class, 'addBookmark']);
        Route::delete('/bookmarks/{bookmark}', [ProfileController::class, 'removeBookmark']);
        
        // Dashboard Data
        Route::get('/dashboard', [ProgressController::class, 'dashboard']);
    });
});
