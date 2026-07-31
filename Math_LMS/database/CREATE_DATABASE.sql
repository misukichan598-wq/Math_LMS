-- ============================================================
-- Math LMS - Complete Database Schema
-- Database: math_lms
-- ============================================================

CREATE DATABASE IF NOT EXISTS math_lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE math_lms;

-- ============================================================
-- ADMINS
-- ============================================================
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    NAME VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    PASSWORD VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- STUDENTS
-- ============================================================
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_number VARCHAR(50) UNIQUE DEFAULT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    PASSWORD VARCHAR(255) NOT NULL,
    grade_level VARCHAR(20) DEFAULT NULL,
    section VARCHAR(50) DEFAULT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    total_points INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- LESSONS
-- ============================================================
CREATE TABLE lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    grade_level VARCHAR(20) DEFAULT NULL,
    thumbnail VARCHAR(255) DEFAULT NULL,
    order_num INT DEFAULT 0,
    is_published TINYINT(1) DEFAULT 1,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
);

-- ============================================================
-- LESSON OBJECTIVES
-- ============================================================
CREATE TABLE lesson_objectives (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lesson_id INT NOT NULL,
    objective TEXT NOT NULL,
    order_num INT DEFAULT 0,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
);

-- ============================================================
-- LESSON TOPICS
-- ============================================================
CREATE TABLE lesson_topics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lesson_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT DEFAULT NULL,
    pdf_file VARCHAR(255) DEFAULT NULL,
    order_num INT DEFAULT 0,
    is_published TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
);

-- ============================================================
-- QUIZZES (per topic)
-- ============================================================
CREATE TABLE quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    topic_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    passing_score INT DEFAULT 60,
    time_limit INT DEFAULT NULL COMMENT 'in minutes, NULL = no limit',
    is_published TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (topic_id) REFERENCES lesson_topics(id) ON DELETE CASCADE
);

-- ============================================================
-- PRE-TESTS (per lesson)
-- ============================================================
CREATE TABLE pre_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lesson_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    passing_score INT DEFAULT 60,
    time_limit INT DEFAULT NULL,
    is_published TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
);

-- ============================================================
-- POST-TESTS (per lesson - same question pool as pre-test)
-- ============================================================
CREATE TABLE post_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lesson_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    passing_score INT DEFAULT 60,
    time_limit INT DEFAULT NULL,
    is_published TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
);

-- ============================================================
-- QUESTIONS (shared pool for quiz / pre-test / post-test)
-- question_type: 'quiz' | 'pretest' | 'posttest'
-- ref_id: quiz_id, pre_test_id, or post_test_id depending on type
-- ============================================================
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_type ENUM('quiz','pretest','posttest') NOT NULL,
    ref_id INT NOT NULL COMMENT 'quiz_id or pre_test_id or post_test_id',
    question_text TEXT NOT NULL,
    question_image VARCHAR(255) DEFAULT NULL,
    order_num INT DEFAULT 0,
    points INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- CHOICES (for each question)
-- ============================================================
CREATE TABLE choices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    choice_text VARCHAR(500) NOT NULL,
    is_correct TINYINT(1) DEFAULT 0,
    order_num INT DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- ============================================================
-- SCORES
-- score_type: 'quiz' | 'pretest' | 'posttest'
-- ============================================================
CREATE TABLE scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    score_type ENUM('quiz','pretest','posttest') NOT NULL,
    ref_id INT NOT NULL COMMENT 'quiz_id, pre_test_id, or post_test_id',
    lesson_id INT NOT NULL,
    raw_score INT DEFAULT 0,
    total_items INT DEFAULT 0,
    percentage DECIMAL(5,2) DEFAULT 0.00,
    passed TINYINT(1) DEFAULT 0,
    attempt_number INT DEFAULT 1,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
);

-- ============================================================
-- SCORE ANSWERS (student answers per attempt)
-- ============================================================
CREATE TABLE score_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    score_id INT NOT NULL,
    question_id INT NOT NULL,
    chosen_choice_id INT DEFAULT NULL,
    is_correct TINYINT(1) DEFAULT 0,
    FOREIGN KEY (score_id) REFERENCES scores(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- ============================================================
-- STUDENT PROGRESS
-- ============================================================
CREATE TABLE student_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    lesson_id INT NOT NULL,
    topic_id INT DEFAULT NULL,
    STATUS ENUM('not_started','in_progress','completed') DEFAULT 'not_started',
    pretest_done TINYINT(1) DEFAULT 0,
    posttest_done TINYINT(1) DEFAULT 0,
    topics_completed INT DEFAULT 0,
    total_topics INT DEFAULT 0,
    progress_percent DECIMAL(5,2) DEFAULT 0.00,
    started_at TIMESTAMP NULL DEFAULT NULL,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_student_lesson (student_id, lesson_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
);

-- ============================================================
-- TOPIC PROGRESS
-- ============================================================
CREATE TABLE topic_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    lesson_id INT NOT NULL,
    topic_id INT NOT NULL,
    is_completed TINYINT(1) DEFAULT 0,
    quiz_done TINYINT(1) DEFAULT 0,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY unique_student_topic (student_id, topic_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (topic_id) REFERENCES lesson_topics(id) ON DELETE CASCADE
);

-- ============================================================
-- ACHIEVEMENTS / BADGES
-- ============================================================
CREATE TABLE achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    NAME VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    icon VARCHAR(255) DEFAULT NULL,
    badge_color VARCHAR(20) DEFAULT '#1976D2',
    condition_type ENUM('pretest_pass','posttest_pass','lesson_complete','perfect_score','first_lesson','quiz_streak') NOT NULL,
    condition_value INT DEFAULT 1 COMMENT 'threshold value for the condition',
    points_reward INT DEFAULT 10,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- STUDENT ACHIEVEMENTS
-- ============================================================
CREATE TABLE student_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    achievement_id INT NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_student_achievement (student_id, achievement_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
);

-- ============================================================
-- LEADERBOARD (computed and cached)
-- ============================================================
CREATE TABLE leaderboard (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL UNIQUE,
    total_points INT DEFAULT 0,
    lessons_completed INT DEFAULT 0,
    perfect_scores INT DEFAULT 0,
    average_score DECIMAL(5,2) DEFAULT 0.00,
    rank_position INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ============================================================
-- ANNOUNCEMENTS
-- ============================================================
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    is_pinned TINYINT(1) DEFAULT 0,
    is_published TINYINT(1) DEFAULT 1,
    published_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
);

-- ============================================================
-- DEFAULT ADMIN SEED
-- Password: admin123 (bcrypt hashed)
-- ============================================================
INSERT INTO admins (NAME, email, PASSWORD) VALUES
('Administrator', 'admin@mathlms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- ============================================================
-- DEFAULT ACHIEVEMENTS SEED
-- ============================================================
INSERT INTO achievements (NAME, description, icon, badge_color, condition_type, condition_value, points_reward) VALUES
('First Step', 'Completed your first lesson', 'star', '#FFD700', 'first_lesson', 1, 20),
('Pre-Test Ace', 'Passed a pre-test on first try', 'military_tech', '#1976D2', 'pretest_pass', 1, 15),
('Post-Test Champion', 'Passed a post-test after completing a lesson', 'emoji_events', '#4CAF50', 'posttest_pass', 1, 25),
('Perfect Score', 'Got 100% on any quiz or test', 'workspace_premium', '#FF6F00', 'perfect_score', 100, 50),
('Lesson Master', 'Completed 3 lessons', 'school', '#9C27B0', 'lesson_complete', 3, 40),
('Quiz Streak', 'Passed 5 quizzes in a row', 'local_fire_department', '#F44336', 'quiz_streak', 5, 30);
