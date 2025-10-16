# Teacher Settings Page Documentation

## Overview

The Teacher Settings page provides a comprehensive interface for teachers to manage their account, preferences, and view their teaching statistics.

## File Location

`teacher/settings.php`

## Features

### 1. Teacher Info Card

**Display:**

- Teacher's full name
- Username
- Email address
- Role confirmation (Teacher)
- Professional appearance with green gradient theme

### 2. Profile Information Management

**Editable Fields:**

- **Full Name**: Teacher's full name (required, max 100 characters)
- **Email**: Contact email (required, validated for uniqueness)
- **Professional Bio**: Teaching experience and qualifications (optional, max 500 characters)

**Non-Editable Fields:**

- Username (displayed but cannot be changed)

**Features:**

- Email uniqueness validation
- Automatic timestamp updates
- CSRF protection
- Success/error messaging
- Redirect after submission to prevent form resubmission

### 3. Password Change

**Fields:**

- Current Password (required)
- New Password (required, min 6 characters)
- Confirm New Password (must match)

**Validation:**

- Current password verification
- Password length check (minimum 6 characters)
- Password confirmation match
- Secure password hashing (PASSWORD_DEFAULT)

**Security:**

- CSRF token validation
- Password verification before change
- Secure password storage with bcrypt

### 4. Application Preferences

**Settings:**

- **Theme Selection**:

  - Light
  - Dark
  - Auto (follows system preference)

- **Notifications**:

  - In-app notifications (for student activity and updates)
  - Email notifications (for quiz submissions and important updates)

- **Privacy**:
  - Show profile publicly (allow students to view profile)

**Features:**

- Checkbox controls for boolean settings
- Dropdown for theme selection
- Auto-save to database
- Default values for new users

### 5. Teaching Statistics Dashboard

**Metrics Displayed:**

1. **Quizzes Created**: Total quizzes authored by teacher
2. **Questions Created**: Total questions in all teacher's quizzes
3. **Quiz Attempts**: Total student attempts on teacher's quizzes
4. **Total Students**: Total students in the system
5. **Resources Uploaded**: Notes, e-books, and past papers uploaded
6. **Announcements Made**: Total announcements created

**Visual Design:**

- Card-based layout
- Icon representation for each metric
- Hover effects for interactivity
- Green color scheme matching teacher theme
- Responsive grid layout

### 6. Danger Zone

**Features:**

- Account deletion option
- Warning message about irreversibility
- Confirmation dialog
- Placeholder for future implementation

## Technical Details

### Database Tables Used

1. **users**: User profile information
2. **user_settings**: User preferences and settings
3. **quizzes**: Teacher's created quizzes
4. **questions**: Questions in quizzes
5. **quiz_attempts**: Student quiz attempts
6. **notes/ebooks/pastpapers**: Learning resources
7. **announcements**: Teacher announcements

### Security Features

1. **Session Validation**: Teacher session check enforced
2. **CSRF Protection**: All forms include CSRF tokens
3. **SQL Injection Prevention**: Prepared statements used
4. **Password Security**: Bcrypt hashing (PASSWORD_DEFAULT)
5. **Input Validation**: Server-side validation for all inputs
6. **XSS Prevention**: HTML special characters escaped

### Error Handling

1. **Graceful Degradation**: Works even if optional tables don't exist
2. **Schema Compatibility**: Checks for column existence (bio, etc.)
3. **Database Error Logging**: Errors logged via error_log()
4. **User-Friendly Messages**: Clear error messages displayed

## Usage Instructions

### Accessing Settings

1. Click "Settings" in the sidebar
2. Or navigate directly to `/teacher/settings.php`

### Updating Profile

1. Navigate to "Profile Information" section
2. Edit desired fields (Full Name, Email, Bio)
3. Click "Update Profile"
4. Success message confirms update

### Changing Password

1. Navigate to "Change Password" section
2. Enter current password
3. Enter new password (min 6 characters)
4. Confirm new password
5. Click "Change Password"
6. Success message confirms change

### Updating Preferences

1. Navigate to "Application Preferences" section
2. Select theme from dropdown
3. Toggle notification checkboxes
4. Toggle profile visibility
5. Click "Save Preferences"
6. Success message confirms update

### Viewing Statistics

- Statistics automatically load on page load
- Cards show real-time data from database
- Hover over cards for visual effect
- No interaction required

## Styling & Design

### Color Scheme (Teacher Theme)

- Primary: `#10b981` (Emerald Green)
- Secondary: `#059669` (Dark Emerald)
- Background: `#f4f7fc` (Light Blue-Gray)
- Text: `#2d3748` (Dark Gray)

### Responsive Design

- Mobile-friendly layout
- Flexible grid system for statistics
- Touch-friendly form elements
- Readable on all screen sizes

### Visual Elements

- Gradient backgrounds for headers
- Card-based sections with shadows
- Icon integration throughout
- Smooth hover transitions
- Professional green theme

## Installation

### Prerequisites

1. Teacher session check configured
2. Database tables created (users, user_settings)
3. CSRF protection implemented

### Setup Steps

1. File is already created at `teacher/settings.php`
2. Sidebar link already added
3. No additional configuration needed

### Database Requirements

**Required Tables:**

- `users` (with columns: user_id, username, email, full_name, bio, password_hash, updated_at)
- `user_settings` (with columns: user_id, theme, notifications_enabled, email_notifications, show_profile_publicly, timezone, updated_at)

**Optional Tables (for statistics):**

- `quizzes`, `questions`, `quiz_attempts`
- `notes`, `ebooks`, `pastpapers`
- `announcements`

## Future Enhancements

### Possible Additions

1. **Profile Picture Upload**: Allow teachers to upload profile photos
2. **Teaching Schedule**: Set availability hours
3. **Notification Preferences**: Granular control over notification types
4. **Language Settings**: Multiple language support
5. **Timezone Selection**: Automatic time adjustment
6. **Two-Factor Authentication**: Enhanced security
7. **Activity Log**: View recent account activities
8. **Export Data**: Download personal data (GDPR compliance)
9. **API Keys**: Generate API keys for integrations
10. **Teaching Preferences**: Quiz default settings, grading preferences

### Planned Features

1. **Email Verification**: Verify email changes
2. **Account Recovery**: Enhanced account recovery options
3. **Session Management**: View and manage active sessions
4. **Integration Settings**: Connect with external tools
5. **Custom Themes**: Allow custom color schemes

## Troubleshooting

### Issue: Settings not saving

**Solution**:

1. Check database connection
2. Verify user_settings table exists
3. Check browser console for JavaScript errors
4. Verify CSRF token is present

### Issue: Statistics showing 0

**Solution**:

1. Verify database tables exist
2. Check if teacher has created content
3. Review error logs for SQL errors
4. Ensure user_id is correct

### Issue: Password change failing

**Solution**:

1. Verify current password is correct
2. Ensure new password meets requirements (min 6 chars)
3. Check passwords match
4. Review error message for specific issue

### Issue: Email update fails

**Solution**:

1. Check if email is already in use
2. Verify email format is valid
3. Ensure database connection is active
4. Check error logs for details

## Best Practices

### For Teachers

1. Keep email address current for notifications
2. Use strong passwords (8+ characters, mixed case, numbers, symbols)
3. Update bio to help students know your background
4. Check statistics regularly to monitor teaching impact
5. Enable notifications to stay informed

### For Administrators

1. Regularly backup user_settings table
2. Monitor error logs for issues
3. Keep database schema updated
4. Test settings changes in staging first
5. Provide teacher training on settings features

## Support

### Common Questions

**Q: Can I change my username?**
A: No, usernames are permanent for security and consistency.

**Q: How do I reset my password if I forgot it?**
A: Use the password reset link on the login page.

**Q: Can students see my email address?**
A: Only if you enable "Show profile publicly" in preferences.

**Q: What happens to my content if I delete my account?**
A: All quizzes, questions, and resources will be permanently deleted.

## Version History

- **v1.0.0** (October 16, 2025): Initial release
  - Profile management
  - Password change
  - Application preferences
  - Teaching statistics
  - Danger zone

---

**Last Updated**: October 16, 2025  
**Version**: 1.0.0  
**Author**: System Development Team
