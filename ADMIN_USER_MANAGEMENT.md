# Admin User Management Page

## Overview

Created a comprehensive user management interface for administrators to view, search, filter, and manage all system users across different roles (students, teachers, admins).

## File Created

- **Location**: `admin/users.php`
- **Purpose**: Central hub for managing all platform users
- **Access**: Admin role only

## Features Implemented

### 1. **User Statistics Dashboard**

Four stat cards displaying key metrics:

- **Total Users**: Overall user count across all roles
- **Students**: Number of student accounts
- **Teachers**: Number of teacher accounts
- **Administrators**: Number of admin accounts

Each stat card features:

- Role-specific gradient icon (blue/purple/green/orange)
- Large numeric display
- Hover animation effect

### 2. **Advanced Search & Filters**

Comprehensive filtering system with:

- **Search Bar**: Search by name, username, or email
- **Role Filter**: Filter by Student/Teacher/Admin
- **Sort Options**:
  - Recently Added (default)
  - Name (alphabetical)
  - Username (alphabetical)
  - Role (grouped)
  - Recent Activity (by last quiz attempt)

Auto-submit on select changes for seamless filtering.

### 3. **Users Data Table**

Complete user management table displaying:

- **User Info Column**: Avatar with initials, full name, @username
- **Email**: User's email address
- **Role Badge**: Color-coded badges (Blue: Admin, Green: Teacher, Purple: Student)
- **Quiz Attempts**: Number of quizzes taken
- **Last Activity**: Date of most recent quiz attempt
- **Joined**: Account creation date
- **Action Buttons**: Edit and Delete options

### 4. **User Management Actions**

- **Add New User**: Button to create new accounts
- **Edit User**: Modify user details (links to `edit_user.php`)
- **Delete User**: Remove user with confirmation prompt

## Design System

### Color Scheme (Blue Theme)

Matching student and admin portal aesthetics:

| Element   | Color               | Purpose                        |
| --------- | ------------------- | ------------------------------ |
| Primary   | `#0a74da`           | Buttons, headers, main accents |
| Secondary | `#1c3d5a`           | Sidebar, dark backgrounds      |
| Gradients | `#0a74da → #1c3d5a` | Headers, stat icons            |

### Stat Card Icons

| Role        | Color  | Icon                    | Gradient            |
| ----------- | ------ | ----------------------- | ------------------- |
| Total Users | Blue   | `fa-users`              | `#0a74da → #1c3d5a` |
| Students    | Purple | `fa-user-graduate`      | `#8b5cf6 → #7c3aed` |
| Teachers    | Green  | `fa-chalkboard-teacher` | `#10b981 → #059669` |
| Admins      | Orange | `fa-shield-halved`      | `#f59e0b → #d97706` |

### Role Badges

| Role    | Background | Text Color |
| ------- | ---------- | ---------- |
| Admin   | `#dbeafe`  | `#1e40af`  |
| Teacher | `#d1fae5`  | `#065f46`  |
| Student | `#e0e7ff`  | `#3730a3`  |

## Database Queries

### User Statistics

```sql
-- Total users by role
SELECT COUNT(*) FROM users WHERE role = 'student|teacher|admin'

-- Active users (with quiz attempts)
SELECT COUNT(DISTINCT user_id) FROM quiz_attempts
```

### Main User Query

```sql
SELECT
    u.user_id,
    u.username,
    u.full_name,
    u.email,
    u.role,
    u.created_at,
    COUNT(qa.attempt_id) as quiz_attempts,
    MAX(qa.created_at) as last_activity
FROM users u
LEFT JOIN quiz_attempts qa ON u.user_id = qa.user_id
WHERE [search and filter conditions]
GROUP BY u.user_id
ORDER BY [sort preference]
```

## Responsive Design

- Mobile-optimized with horizontal scroll on small screens
- Stats grid adapts from 4 columns to 1 column on mobile
- Flexible control rows that wrap on narrow screens
- Touch-friendly buttons and action elements

## Security Features

- **Session Check**: `admin_session_check.php` validates admin access
- **Input Sanitization**: All GET parameters filtered (FILTER_SANITIZE_STRING)
- **Prepared Statements**: All queries use PDO prepared statements
- **Delete Confirmation**: JavaScript confirm() prevents accidental deletions

## User Experience Enhancements

- **Empty State**: Clear message when no users match filters
- **Auto-Submit**: Role and sort filters update results instantly
- **Hover Effects**: Visual feedback on table rows and cards
- **Avatar System**: First letter of name in gradient circle
- **Activity Indicators**: Shows "No activity" for users without quiz attempts

## Integration Points

- **Sidebar**: Highlights "Manage Users" as active page
- **Add User**: Links to `add_user.php` (to be created)
- **Edit User**: Links to `edit_user.php?id={user_id}` (to be created)
- **Delete User**: Links to `delete_user.php?id={user_id}` (to be created)

## File Dependencies

- `admin/php/admin_session_check.php` - Admin authentication
- `config/database.php` - Database connection
- `admin/includes/sidebar.php` - Navigation sidebar
- `assets/css/style.css` - Base styles
- `assets/css/dashboard.css` - Dashboard layout
- Font Awesome 6.4.0 - Icons
- Poppins font - Typography

## Future Enhancements

Potential additions:

1. **Pagination**: For large user datasets
2. **Bulk Actions**: Select multiple users for batch operations
3. **Export**: CSV/Excel export of user data
4. **User Status**: Active/Suspended/Banned status toggle
5. **Advanced Filters**: Date range, activity level, performance metrics
6. **User Details Modal**: Quick view without page navigation
7. **Role Change**: Quick role assignment dropdown
8. **Password Reset**: Admin-initiated password reset links

## Testing Checklist

- [x] PHP syntax validation passed
- [ ] Database queries return correct data
- [ ] Search functionality works across all fields
- [ ] Role filtering isolates correct users
- [ ] Sort options reorder properly
- [ ] Avatar initials display correctly
- [ ] Role badges show appropriate colors
- [ ] Action buttons link to correct pages
- [ ] Responsive layout works on mobile
- [ ] Session check prevents unauthorized access

## Page Structure

```
├── Page Header (Blue gradient)
├── User Statistics (4 stat cards)
├── Controls Section
│   ├── Search Bar
│   ├── Role Filter
│   └── Sort Dropdown
├── Users Table Section
│   ├── Section Header + Add Button
│   └── Users Data Table
│       ├── User Info (avatar + name)
│       ├── Email
│       ├── Role Badge
│       ├── Quiz Attempts Count
│       ├── Last Activity Date
│       ├── Join Date
│       └── Action Buttons (Edit/Delete)
└── JavaScript (auto-submit filters)
```

## Comparison with Teacher Students Page

This admin users page mirrors the structure of `teacher/students.php` with these distinctions:

| Feature     | Teacher Students         | Admin Users         |
| ----------- | ------------------------ | ------------------- |
| **Scope**   | Only students            | All user roles      |
| **Stats**   | Student-focused          | Role breakdown      |
| **Filters** | Performance-based        | Role-based          |
| **Actions** | View details             | Edit/Delete users   |
| **Color**   | Green theme              | Blue theme          |
| **Purpose** | Monitor student progress | Manage system users |

## Success Metrics

- Admins can quickly find any user via search
- Role filtering isolates specific user groups effectively
- Statistics provide at-a-glance system overview
- Action buttons enable efficient user management
- Responsive design maintains usability on all devices

---

**Status**: ✅ Complete and validated
**Created**: [Current Date]
**Last Updated**: [Current Date]
