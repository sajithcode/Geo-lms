# Role-Based Dashboard Redirection System

## Overview

This document describes the role-based authentication and dashboard redirection system implemented in Geo-LMS.

## User Roles

The system supports three user roles:

1. **Student** - Default role for registered users

   - Dashboard: `/pages/dashboard.php`
   - Access: Student learning resources, quizzes, performance tracking

2. **Teacher** - Instructor/educator role

   - Dashboard: `/teacher/dashboard.php`
   - Access: Quiz management, resource uploads, student tracking

3. **Admin** - System administrator role
   - Dashboard: `/admin/dashboard.php`
   - Access: Full system management, user management, all features

## Login Flow

### Main Login Process (`php/login_process.php`)

When a user logs in through `/auth/index.php`, the system:

1. Validates credentials (username/email and password)
2. Verifies the password hash
3. Creates session variables:

   - `$_SESSION["loggedin"]` = true
   - `$_SESSION["id"]` = user_id
   - `$_SESSION["username"]` = username
   - `$_SESSION["role"]` = user role

4. **Redirects based on role:**
   ```php
   if ($role === 'admin') {
       header("location: ../admin/dashboard.php");
   } elseif ($role === 'teacher') {
       header("location: ../teacher/dashboard.php");
   } else {
       header("location: ../pages/dashboard.php"); // student
   }
   ```

### Admin Login Process (`admin/php/admin_login_process.php`)

Admin-specific login at `/admin/login.php`:

1. Validates credentials
2. **Verifies admin role** - rejects non-admin users
3. Updates last_login timestamp
4. Redirects to admin dashboard

## Session Protection

### Student Pages (`php/session_check.php`)

Protects student pages in `/pages/` directory:

```php
// Redirects if not logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../auth/index.php");
    exit;
}

// Role-based redirection
if ($_SESSION["role"] === 'admin') {
    header("location: ../admin/dashboard.php");
} elseif ($_SESSION["role"] === 'teacher') {
    header("location: ../teacher/dashboard.php");
}
```

**Result:** Admins and teachers are automatically redirected to their dashboards if they try to access student pages.

### Admin Pages (`admin/php/admin_session_check.php`)

Protects admin pages in `/admin/` directory:

```php
// Redirects if not logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../admin/login.php");
    exit;
}

// Verifies admin role
if ($_SESSION["role"] !== 'admin') {
    header("location: ../pages/dashboard.php");
    exit;
}
```

**Result:** Only users with admin role can access admin pages.

### Teacher Pages (`teacher/php/teacher_session_check.php`)

Protects teacher pages in `/teacher/` directory:

```php
// Redirects if not logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../auth/index.php");
    exit;
}

// Verifies teacher role
if ($_SESSION["role"] !== 'teacher') {
    if ($_SESSION["role"] === 'admin') {
        header("location: ../admin/dashboard.php");
    } else {
        header("location: ../pages/dashboard.php");
    }
    exit;
}
```

**Result:** Only teachers can access teacher pages. Others are redirected to their appropriate dashboards.

## Dashboard Features

### Student Dashboard (`pages/dashboard.php`)

- Welcome message
- Quick access cards:
  - Student Registration
  - Quizzes
  - Notifications
  - Performance Tracking
  - Teacher Interaction
  - Learning Resources
  - Feedback

### Teacher Dashboard (`teacher/dashboard.php`)

- Statistics:
  - Total Students
  - Total Quizzes
  - Quiz Attempts
  - Learning Resources
- Quick Actions:
  - Create Quiz
  - Upload Resources
  - Track Progress
- Recent Quiz Attempts table
- Quick tips and guidance

### Admin Dashboard (`admin/dashboard.php`)

- System Statistics:
  - Total Users
  - Total Quizzes
  - Quiz Attempts
  - Feedback Messages
- Navigation Links:
  - Manage Users
  - Manage Quizzes
  - Quiz Categories
  - Manage Resources
  - View Feedback
- Recent Users table
- Recent Feedback table

## Database Schema

### Users Table

```sql
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'teacher', 'admin') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);
```

## Implementation Files

### Modified Files:

1. **php/login_process.php** - Added role-based redirection
2. **php/session_check.php** - Added role verification and redirection
3. **admin/php/admin_session_check.php** - Already had role verification
4. **admin/php/admin_login_process.php** - Already had admin-only access

### New Files:

1. **teacher/dashboard.php** - Teacher dashboard interface
2. **teacher/php/teacher_session_check.php** - Teacher session protection

## Usage

### For Developers

To protect a new student page:

```php
<?php
require_once '../php/session_check.php';
// Your page content
?>
```

To protect a new admin page:

```php
<?php
require_once 'php/admin_session_check.php';
// Your admin page content
?>
```

To protect a new teacher page:

```php
<?php
require_once 'php/teacher_session_check.php';
// Your teacher page content
?>
```

### For Users

1. **Register** at `/auth/register.php` (default role: student)
2. **Login** at `/auth/index.php`
3. **Automatically redirected** to appropriate dashboard based on role
4. **Protected pages** automatically redirect unauthorized users

### Changing User Roles

Roles can be changed directly in the database:

```sql
-- Make user a teacher
UPDATE users SET role = 'teacher' WHERE user_id = 1;

-- Make user an admin
UPDATE users SET role = 'admin' WHERE user_id = 1;

-- Make user a student
UPDATE users SET role = 'student' WHERE user_id = 1;
```

Or through the admin interface (if user management is implemented).

## Security Features

1. **Password Hashing** - Uses `password_hash()` with bcrypt
2. **Session Regeneration** - `session_regenerate_id()` on login
3. **CSRF Protection** - Token validation on all forms
4. **Role Verification** - Checked on every protected page
5. **Prepared Statements** - SQL injection prevention
6. **Input Sanitization** - `htmlspecialchars()` on output

## Testing

### Test Accounts

Create test accounts for each role:

```sql
-- Student account (password: Student123)
INSERT INTO users (username, email, password_hash, role)
VALUES ('student1', 'student@test.com', '$2y$10$...', 'student');

-- Teacher account (password: Teacher123)
INSERT INTO users (username, email, password_hash, role)
VALUES ('teacher1', 'teacher@test.com', '$2y$10$...', 'teacher');

-- Admin account (password: Admin123)
INSERT INTO users (username, email, password_hash, role)
VALUES ('admin1', 'admin@test.com', '$2y$10$...', 'admin');
```

### Test Scenarios

1. ✅ Student logs in → Redirected to `/pages/dashboard.php`
2. ✅ Teacher logs in → Redirected to `/teacher/dashboard.php`
3. ✅ Admin logs in → Redirected to `/admin/dashboard.php`
4. ✅ Student tries to access admin page → Redirected to student dashboard
5. ✅ Student tries to access teacher page → Redirected to student dashboard
6. ✅ Teacher tries to access admin page → Redirected to teacher dashboard
7. ✅ Admin tries to access student page → Redirected to admin dashboard
8. ✅ Unauthenticated user → Redirected to login page

## Troubleshooting

### Issue: Wrong dashboard after login

**Solution:** Check `role` column in database for the user

### Issue: Session not persisting

**Solution:** Verify `session_start()` is called before any output

### Issue: Redirects in a loop

**Solution:** Check that session variables are set correctly after login

### Issue: Cannot access pages

**Solution:** Verify the session check file path is correct for the directory structure

## Future Enhancements

1. **Teacher-specific features:**

   - Create/edit quizzes
   - Upload learning resources
   - View student performance
   - Grade assignments

2. **Admin enhancements:**

   - User role management interface
   - System settings
   - Analytics dashboard
   - Backup and restore

3. **Permission system:**
   - Granular permissions beyond roles
   - Custom role creation
   - Permission inheritance

## Conclusion

The role-based dashboard redirection system provides secure, automatic routing of users to their appropriate interfaces based on their assigned roles. All pages are protected with session checks, and cross-role access is prevented through automatic redirection.

---

**Last Updated:** October 16, 2025
**Version:** 1.0
**Author:** Geo-LMS Development Team
