<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Student\DashboardController;
use App\Http\Controllers\Student\LessonController as StudentLessonController;
use App\Http\Controllers\Student\AssessmentController as StudentAssessmentController;
use App\Http\Controllers\Student\ActivityController as StudentActivityController;
use App\Http\Controllers\Student\ProfileController;
use App\Http\Controllers\Student\ProgressController;
use App\Http\Controllers\Student\HallOfFameController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\StudentManagementController;
use App\Http\Controllers\Admin\LessonManagementController;
use App\Http\Controllers\Admin\ActivityManagementController;
use App\Http\Controllers\Admin\AssessmentManagementController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\ReportController;

Route::get('/', function () {
    if (auth()->check()) {
        if (auth()->user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('student.dashboard');
    }
    return redirect()->route('login');
})->name('home');

// Guest routes (Authentication)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

// Student routes
Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/picture', [ProfileController::class, 'updatePicture'])->name('profile.picture');
    
    // Lessons
    Route::get('/lessons', [StudentLessonController::class, 'index'])->name('lessons.index');
    Route::get('/lessons/{lesson}', [StudentLessonController::class, 'show'])->name('lessons.show');
    Route::get('/lessons/{lesson}/section/{section}', [StudentLessonController::class, 'showSection'])->name('lessons.section');
    Route::post('/lessons/{lesson}/start', [StudentLessonController::class, 'start'])->name('lessons.start');
    Route::post('/lessons/{lesson}/section/{section}/complete', [StudentLessonController::class, 'completeSection'])->name('lessons.section.complete');
    
    // Activities
    Route::get('/activities/{activity}', [StudentActivityController::class, 'show'])->name('activities.show');
    Route::post('/activities/{activity}/submit', [StudentActivityController::class, 'submit'])->name('activities.submit');
    
    // Assessments
    Route::get('/assessment/initial', [StudentAssessmentController::class, 'showInitial'])->name('assessment.initial');
    Route::post('/assessment/initial/start', [StudentAssessmentController::class, 'startInitial'])->name('assessment.initial.start');
    Route::get('/assessment/initial/take/{attempt}', [StudentAssessmentController::class, 'takeInitial'])->name('assessment.initial.take');
    Route::post('/assessment/initial/submit/{attempt}', [StudentAssessmentController::class, 'submitInitial'])->name('assessment.initial.submit');
    
    Route::get('/assessment/final', [StudentAssessmentController::class, 'showFinal'])->name('assessment.final');
    Route::post('/assessment/final/start', [StudentAssessmentController::class, 'startFinal'])->name('assessment.final.start');
    Route::get('/assessment/final/take/{attempt}', [StudentAssessmentController::class, 'takeFinal'])->name('assessment.final.take');
    Route::post('/assessment/final/submit/{attempt}', [StudentAssessmentController::class, 'submitFinal'])->name('assessment.final.submit');
    
    Route::get('/assessment/result/{attempt}', [StudentAssessmentController::class, 'showResult'])->name('assessment.result');
    
    // Progress and Scores
    Route::get('/progress', [ProgressController::class, 'index'])->name('progress.index');
    Route::get('/scores', [ProgressController::class, 'scores'])->name('scores.index');
    Route::get('/history', [ProgressController::class, 'history'])->name('history.index');
    
    // Hall of Fame
    Route::get('/hall-of-fame', [HallOfFameController::class, 'index'])->name('hall-of-fame.index');
    
    // Bookmarks
    Route::post('/bookmarks', [ProfileController::class, 'addBookmark'])->name('bookmarks.add');
    Route::delete('/bookmarks/{bookmark}', [ProfileController::class, 'removeBookmark'])->name('bookmarks.remove');
    
    // Notifications
    Route::get('/notifications', [ProfileController::class, 'notifications'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [ProfileController::class, 'markNotificationRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [ProfileController::class, 'markAllNotificationsRead'])->name('notifications.read-all');
});

// Admin routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    
    // Student Management
    Route::resource('students', StudentManagementController::class);
    Route::post('/students/{user}/reset-progress', [StudentManagementController::class, 'resetProgress'])->name('students.reset-progress');
    Route::post('/students/{user}/toggle-status', [StudentManagementController::class, 'toggleStatus'])->name('students.toggle-status');
    
    // Lesson Management
    Route::resource('lessons', LessonManagementController::class);
    Route::post('/lessons/{lesson}/upload-pdf', [LessonManagementController::class, 'uploadPdf'])->name('lessons.upload-pdf');
    Route::get('/lessons/{lesson}/sections', [LessonManagementController::class, 'manageSections'])->name('lessons.sections');
    Route::post('/lessons/{lesson}/sections', [LessonManagementController::class, 'storeSection'])->name('lessons.sections.store');
    Route::put('/lessons/{lesson}/sections/{section}', [LessonManagementController::class, 'updateSection'])->name('lessons.sections.update');
    Route::delete('/lessons/{lesson}/sections/{section}', [LessonManagementController::class, 'deleteSection'])->name('lessons.sections.destroy');
    Route::post('/lessons/{lesson}/sections/reorder', [LessonManagementController::class, 'reorderSections'])->name('lessons.sections.reorder');
    
    // Activity Management
    Route::get('/activities', [ActivityManagementController::class, 'index'])->name('activities.index');
    Route::get('/sections/{section}/activities', [ActivityManagementController::class, 'manageActivities'])->name('sections.activities');
    Route::post('/sections/{section}/activities', [ActivityManagementController::class, 'store'])->name('activities.store');
    Route::get('/activities/{activity}/edit', [ActivityManagementController::class, 'edit'])->name('activities.edit');
    Route::put('/activities/{activity}', [ActivityManagementController::class, 'update'])->name('activities.update');
    Route::delete('/activities/{activity}', [ActivityManagementController::class, 'destroy'])->name('activities.destroy');
    Route::post('/activities/{activity}/questions', [ActivityManagementController::class, 'addQuestion'])->name('activities.questions.store');
    Route::put('/activities/{activity}/questions/{question}', [ActivityManagementController::class, 'updateQuestion'])->name('activities.questions.update');
    Route::delete('/activities/{activity}/questions/{question}', [ActivityManagementController::class, 'deleteQuestion'])->name('activities.questions.destroy');
    
    // Assessment Management
    Route::resource('assessments', AssessmentManagementController::class);
    Route::post('/assessments/{assessment}/questions', [AssessmentManagementController::class, 'addQuestion'])->name('assessments.questions.store');
    Route::put('/assessments/{assessment}/questions/{question}', [AssessmentManagementController::class, 'updateQuestion'])->name('assessments.questions.update');
    Route::delete('/assessments/{assessment}/questions/{question}', [AssessmentManagementController::class, 'deleteQuestion'])->name('assessments.questions.destroy');
    Route::post('/assessments/{assessment}/import-questions', [AssessmentManagementController::class, 'importQuestions'])->name('assessments.questions.import');
    
    // Announcements
    Route::resource('announcements', AnnouncementController::class);
    
    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/student-performance', [ReportController::class, 'studentPerformance'])->name('reports.student-performance');
    Route::get('/reports/assessment-comparison', [ReportController::class, 'assessmentComparison'])->name('reports.assessment-comparison');
    Route::get('/reports/lesson-completion', [ReportController::class, 'lessonCompletion'])->name('reports.lesson-completion');
    Route::get('/reports/hall-of-fame', [ReportController::class, 'hallOfFame'])->name('reports.hall-of-fame');
    Route::post('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    
    // Database Backup
    Route::post('/backup', [AdminDashboardController::class, 'backup'])->name('backup');
});
