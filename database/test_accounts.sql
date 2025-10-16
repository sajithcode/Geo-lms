-- =====================================================
-- ROLE-BASED TEST ACCOUNTS SETUP
-- Geo-LMS - Learning Management System
-- =====================================================

-- This SQL file creates test accounts for each role
-- Default password for all accounts: Test123

-- =====================================================
-- 1. STUDENT TEST ACCOUNT
-- =====================================================
-- Username: student_test
-- Email: student@test.com
-- Password: Test123
-- Role: student

INSERT INTO users (username, email, password_hash, role, created_at) 
VALUES (
    'student_test', 
    'student@test.com', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- bcrypt hash for "Test123"
    'student',
    CURRENT_TIMESTAMP
);

-- =====================================================
-- 2. TEACHER TEST ACCOUNT
-- =====================================================
-- Username: teacher_test
-- Email: teacher@test.com
-- Password: Test123
-- Role: teacher

INSERT INTO users (username, email, password_hash, role, created_at) 
VALUES (
    'teacher_test', 
    'teacher@test.com', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- bcrypt hash for "Test123"
    'teacher',
    CURRENT_TIMESTAMP
);

-- =====================================================
-- 3. ADMIN TEST ACCOUNT
-- =====================================================
-- Username: admin_test
-- Email: admin@test.com
-- Password: Test123
-- Role: admin

INSERT INTO users (username, email, password_hash, role, created_at) 
VALUES (
    'admin_test', 
    'admin@test.com', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- bcrypt hash for "Test123"
    'admin',
    CURRENT_TIMESTAMP
);

-- =====================================================
-- VERIFY ACCOUNTS CREATED
-- =====================================================

SELECT 
    user_id,
    username,
    email,
    role,
    created_at
FROM users
WHERE username IN ('student_test', 'teacher_test', 'admin_test')
ORDER BY role;

-- =====================================================
-- HOW TO USE THESE ACCOUNTS
-- =====================================================

-- 1. Run this SQL script in your phpMyAdmin or MySQL client
-- 2. Go to http://localhost/lms/auth/index.php
-- 3. Login with any of these credentials:
--    
--    STUDENT:
--    Username: student_test
--    Password: Test123
--    → Will redirect to: /pages/dashboard.php
--    
--    TEACHER:
--    Username: teacher_test
--    Password: Test123
--    → Will redirect to: /teacher/dashboard.php
--    
--    ADMIN:
--    Username: admin_test
--    Password: Test123
--    → Will redirect to: /admin/dashboard.php

-- =====================================================
-- CHANGE USER ROLE (if needed)
-- =====================================================

-- Make an existing user a teacher:
-- UPDATE users SET role = 'teacher' WHERE username = 'yourusername';

-- Make an existing user an admin:
-- UPDATE users SET role = 'admin' WHERE username = 'yourusername';

-- Make an existing user a student:
-- UPDATE users SET role = 'student' WHERE username = 'yourusername';

-- =====================================================
-- DELETE TEST ACCOUNTS (when done testing)
-- =====================================================

-- DELETE FROM users WHERE username IN ('student_test', 'teacher_test', 'admin_test');

-- =====================================================
-- NOTES
-- =====================================================

-- Password Hash: The password_hash value is generated using PHP's password_hash()
-- function with PASSWORD_DEFAULT algorithm (bcrypt). The hash shown is for "Test123".

-- Security: These are TEST accounts only. In production:
-- 1. Use strong, unique passwords
-- 2. Never commit passwords to version control
-- 3. Change default admin credentials immediately
-- 4. Use environment variables for sensitive data

-- Role Enum: Ensure your users table has role column as:
-- role ENUM('student', 'teacher', 'admin') DEFAULT 'student'
