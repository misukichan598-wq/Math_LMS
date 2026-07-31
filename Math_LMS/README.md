# Mathematics Learning Management System (Math LMS)

A comprehensive Learning Management System built with Laravel 12 (backend/web) and Flutter (mobile app) for teaching Grade 9 Mathematics - Solving Quadratic Equations.

## 🚀 Project Status

### ✅ Completed Components (5/19 tasks)

1. **Laravel Project Structure** ✅
   - Composer.json with Laravel 12, Sanctum, DomPDF, Maatwebsite Excel
   - Package.json with Vite, Bootstrap 5, Chart.js
   - Environment configuration (.env.example)
   - Configuration files (app, database, auth, sanctum)

2. **Database Architecture** ✅
   - 20 comprehensive migrations with proper relationships
   - Tables: users, personal_access_tokens, student_profiles, lessons, lesson_sections, activities, activity_questions, assessments, assessment_questions, student_progress, activity_attempts, assessment_attempts, assessment_answers, student_scores, hall_of_fame, announcements, notifications, learning_history, bookmarks, audit_logs

3. **Eloquent Models** ✅
   - 16 fully-featured models with relationships
   - User (HasApiTokens, SoftDeletes, role-based methods)
   - All relationships properly defined (hasMany, belongsTo, morphTo)
   - Helper methods, scopes, and business logic
   - CheckRole and EnsureEmailIsVerified middleware

4. **Authentication System** ✅
   - Laravel Sanctum implementation for web and API
   - Web routes with role-based access control
   - API v1 routes for mobile app
   - Authentication controllers (Login, Register, ForgotPassword, ResetPassword)
   - API AuthController with token management
   - Request validation classes
   - ApiResponse trait for standardized API responses

5. **REST API Controllers** ✅
   - **AuthController**: Register, login, logout, password reset, token refresh
   - **ProfileController**: Profile management, bookmarks, picture upload
   - **LessonController**: Lessons, sections, start/complete with progress tracking
   - **ActivityController**: View activities, submit answers, instant feedback
   - **AssessmentController**: Initial/final assessments, question-by-question submission
   - **ProgressController**: Overview, lesson progress, time tracking, scores, history
   - **HallOfFameController**: Rankings, top students, personal rank
   - **NotificationController**: List, mark read, delete notifications
   - **AnnouncementController**: Published announcements

6. **Web Controllers** 🚧 (Partial - 2 created)
   - DashboardController ✅
   - ProfileController ✅
   - Remaining: LessonController, AssessmentController, ActivityController, ProgressController, HallOfFameController

### 📋 Remaining Tasks (14/19)

7. **Student Dashboard and Learning Flow**
   - Complete remaining student controllers
   - Implement embedded activity flow
   - Section-by-section learning with activities

8. **Admin Panel Features**
   - Admin dashboard with analytics
   - Student management (view, edit, reset progress, toggle status)
   - Lesson management (CRUD, PDF upload, section organization)
   - Activity management (create activities with questions)
   - Assessment management (import questions from PDF)
   - Announcements management

9. **Blade Views with Bootstrap 5**
   - Authentication views (login, register, password reset)
   - Student dashboard
   - Lesson views
   - Assessment views
   - Profile views
   - Admin panel views
   - Responsive layouts

10. **Progress Tracking and Hall of Fame**
    - Automatic progress calculation
    - Time tracking
    - Score recording
    - Hall of Fame ranking algorithm (already implemented in model)

11. **Notification and Announcement System**
    - Notification creation triggers
    - Real-time notification display
    - Announcement broadcasting

12. **Reporting and Export Features**
    - Student performance reports (PDF/Excel)
    - Assessment comparison reports
    - Lesson completion reports
    - Hall of Fame export

13. **Database Seeders**
    - Admin user seeder
    - Assessment questions from PDF
    - Sample lessons and sections
    - Sample activities

14. **Flutter Mobile Application**
    - Project initialization
    - API service layer
    - State management setup

15. **Flutter Authentication Screens**
    - Login screen
    - Register screen
    - Password reset
    - Profile management

16. **Flutter Student Dashboard and Learning**
    - Dashboard with stats
    - Lesson list and viewer
    - Activity screens
    - Assessment screens
    - Progress tracking
    - Hall of Fame

17. **Railway Deployment Configuration**
    - railway.json
    - Environment variable setup
    - Database migration strategy
    - Build configuration

18. **Security Measures and Validation**
    - CSRF protection (already configured)
    - Input validation (partially done)
    - File upload security
    - Role-based access control (already implemented)
    - SQL injection prevention (using Eloquent)
    - XSS protection

19. **Documentation**
    - API documentation
    - Setup instructions
    - Deployment guide
    - User manual

## 🏗️ Architecture

### Backend (Laravel 12)
- **Framework**: Laravel 12 with PHP 8.3+
- **Authentication**: Laravel Sanctum for both web and API
- **Database**: MySQL with Eloquent ORM
- **API**: RESTful API v1 for mobile app
- **Security**: CSRF protection, input validation, RBAC

### Frontend (Web)
- **Template Engine**: Laravel Blade
- **CSS Framework**: Bootstrap 5
- **JavaScript**: Vanilla JS with AJAX
- **Charts**: Chart.js
- **Build Tool**: Vite

### Mobile (Flutter)
- **Framework**: Flutter with Dart
- **API Integration**: HTTP package with Laravel Sanctum tokens
- **State Management**: To be implemented
- **UI**: Material Design

## 🗂️ Database Schema

### Core Tables
- **users**: User accounts (students and admins)
- **student_profiles**: Extended student information
- **lessons**: Learning lessons with PDF materials
- **lesson_sections**: Sections within lessons
- **activities**: Interactive learning activities
- **activity_questions**: Questions for activities
- **assessments**: Initial and final assessments
- **assessment_questions**: Assessment questions

### Progress Tracking
- **student_progress**: Lesson and section completion
- **activity_attempts**: Student answers to activities
- **assessment_attempts**: Assessment submissions
- **assessment_answers**: Individual assessment answers
- **student_scores**: Consolidated scores

### Additional Features
- **hall_of_fame**: Student rankings
- **notifications**: User notifications
- **announcements**: System announcements
- **learning_history**: Activity logs
- **bookmarks**: Saved learning positions
- **audit_logs**: Admin action tracking

## 📊 Learning Flow (As Per Requirements)

1. **Student Login** → Dashboard (Homepage)
2. **Dashboard**: View stats, progress, announcements
3. **Start Learning** → Check Initial Assessment completion
4. **Initial Assessment** (if not completed):
   - Take assessment based on pre-test-research.pdf
   - One-time only unless admin resets
5. **Lesson Discussion**:
   - Read uploaded PDF content
   - Organized into sections by admin
6. **Embedded Interactive Activities**:
   - Activities appear BETWEEN discussions
   - Must complete required activities before continuing
   - Immediate feedback after each activity
   - Activity types: Multiple Choice, True/False, Matching, Fill Blank, Drag & Drop, Arrange Order, Identify
7. **Lesson Completion**: All sections and activities done
8. **Final Assessment**:
   - Same questions as Initial Assessment
   - For before/after comparison
9. **Results**: Initial vs Final comparison, improvement percentage
10. **Hall of Fame**: Rankings updated automatically

## 🔐 User Roles

### Student
- Register and login
- Take initial assessment (once)
- Access lessons and complete sections
- Complete embedded activities
- Take final assessment
- View progress and scores
- View Hall of Fame
- Receive notifications
- Manage profile

### Administrator
- Login to admin panel
- Manage students (view, edit, reset progress)
- Upload and manage lesson PDFs
- Create lesson sections from PDFs
- Create and manage activities
- Create and manage assessment questions
- Import questions from PDF
- View student progress and reports
- Create announcements
- Generate and export reports
- Backup database

## 🛠️ Installation (Once Complete)

```bash
# Clone repository
git clone <repository-url>
cd Math_LMS

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Configure database in .env file
# Then run migrations
php artisan migrate

# Seed database (once seeders are created)
php artisan db:seed

# Build assets
npm run build

# Start development server
php artisan serve
```

## 📱 API Endpoints

### Authentication
- POST `/api/v1/register` - Register new student
- POST `/api/v1/login` - Login
- POST `/api/v1/logout` - Logout
- POST `/api/v1/forgot-password` - Request password reset
- POST `/api/v1/reset-password` - Reset password

### Lessons (Protected)
- GET `/api/v1/lessons` - List all lessons
- GET `/api/v1/lessons/{id}` - Get lesson details
- GET `/api/v1/lessons/{id}/sections` - Get lesson sections
- POST `/api/v1/lessons/{id}/start` - Start lesson
- POST `/api/v1/lessons/{id}/sections/{section}/complete` - Complete section

### Activities (Protected)
- GET `/api/v1/activities/{id}` - Get activity
- POST `/api/v1/activities/{id}/submit` - Submit activity answers

### Assessments (Protected)
- GET `/api/v1/assessments/initial` - Get initial assessment
- POST `/api/v1/assessments/initial/start` - Start initial assessment
- POST `/api/v1/assessments/initial/submit/{attempt}` - Submit initial assessment
- GET `/api/v1/assessments/final` - Get final assessment
- POST `/api/v1/assessments/final/start` - Start final assessment
- POST `/api/v1/assessments/final/submit/{attempt}` - Submit final assessment

### Progress (Protected)
- GET `/api/v1/progress` - Get user progress
- GET `/api/v1/progress/overview` - Progress overview
- GET `/api/v1/scores` - Get scores
- GET `/api/v1/history` - Learning history

### Hall of Fame (Protected)
- GET `/api/v1/hall-of-fame` - Get rankings
- GET `/api/v1/hall-of-fame/my-rank` - Get personal rank

## 🎯 Key Features

### Implemented ✅
- RESTful API with Sanctum authentication
- Role-based access control
- Comprehensive database schema
- Progress tracking system
- Hall of Fame ranking algorithm
- Learning history logging
- Audit logging
- Notification system
- Announcement system
- File upload handling
- API response standardization

### In Progress 🚧
- Web controllers and views
- Admin panel
- Flutter mobile app

### Pending ⏳
- Report generation
- PDF question import
- Database seeders
- Complete testing
- Deployment configuration

## 📄 License

MIT License

## 👥 Contributors

Development in progress...

---

**Note**: This is a work-in-progress project. Current completion: **26% (5/19 tasks)**
