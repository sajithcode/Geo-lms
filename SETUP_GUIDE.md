# Quick Setup Guide - Learning Resources System

## Step 1: Run Database Migration

### Option A: Using phpMyAdmin (Recommended)

1. Open your browser and go to `http://localhost/phpmyadmin`
2. Click on your `lms` database in the left sidebar
3. Click the "Import" tab at the top
4. Click "Choose File" and select: `C:\xampp\htdocs\lms\database\learning_resources.sql`
5. Scroll down and click "Import" button
6. You should see "Import has been successfully finished"

### Option B: Using MySQL Command Line

1. Open Command Prompt (cmd)
2. Navigate to MySQL bin directory:
   ```
   cd C:\xampp\mysql\bin
   ```
3. Run the migration:
   ```
   mysql -u root -p lms < C:\xampp\htdocs\lms\database\learning_resources.sql
   ```
4. Press Enter (leave password blank if you haven't set one)

### Option C: Using phpMyAdmin SQL Tab

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Select your `lms` database
3. Click the "SQL" tab
4. Open `database/learning_resources.sql` file in a text editor
5. Copy all the SQL code
6. Paste it into the SQL query box in phpMyAdmin
7. Click "Go" button

## Step 2: Verify Installation

After running the migration, verify the tables were created:

```sql
SHOW TABLES LIKE '%resource%';
```

You should see:

- `resource_categories`
- `notes`
- `ebooks`
- `pastpapers`

Check the default categories:

```sql
SELECT * FROM resource_categories;
```

You should see 6 categories:

- Mathematics
- Physics
- Chemistry
- Computer Science
- Engineering
- General

## Step 3: Create Upload Directories

The system will automatically create these directories when you upload the first file, but you can create them manually:

1. Open File Explorer
2. Navigate to: `C:\xampp\htdocs\lms\uploads`
3. Create these folders if they don't exist:
   - `notes`
   - `ebooks`
   - `pastpapers`

## Step 4: Configure PHP Upload Limits

Open `C:\xampp\php\php.ini` and ensure these settings:

```ini
upload_max_filesize = 50M
post_max_size = 55M
max_execution_time = 300
memory_limit = 256M
```

After changing, restart Apache from XAMPP Control Panel.

## Step 5: Test the System

### Test as Teacher:

1. Login as a teacher account
2. Go to "Learning Resources" in the sidebar
3. Try uploading a test PDF file
4. Verify it appears in the table below

### Test as Admin:

1. Login as admin account
2. Go to "Resources" in admin panel
3. Verify you can see all uploaded resources
4. Test upload and delete functionality

### Test as Student:

1. Login as a student account
2. Go to "Learning Resources" → "Notes"
3. You should see the uploaded files
4. Click "Download" to test download handler
5. Try search and filter functionality

## Troubleshooting

### Issue: "Resource Tables Not Found" warning appears

**Solution:** The migration hasn't been run. Follow Step 1 above.

### Issue: Upload fails silently

**Solution:**

- Check PHP error log: `C:\xampp\apache\logs\error.log`
- Verify upload directory permissions
- Check PHP upload limits (Step 4)

### Issue: Download doesn't work

**Solution:**

- Verify file exists in uploads directory
- Check file_path in database matches actual location
- Clear browser cache

### Issue: File too large error

**Solution:**

- Increase PHP limits (Step 4)
- Restart Apache
- Current limit is 50MB per file

## Quick Access URLs

After setup, access the system at:

- **Teacher Resources:** `http://localhost/lms/teacher/resources.php`
- **Admin Resources:** `http://localhost/lms/admin/resources.php`
- **Student Notes:** `http://localhost/lms/pages/notes.php`
- **Student E-books:** `http://localhost/lms/pages/e-books.php`
- **Student Past Papers:** `http://localhost/lms/pages/pastpapers.php`

## Test Accounts

Use these test accounts (from `database/test_accounts.sql`):

### Admin Account

- Username: `admin`
- Password: `admin123`

### Teacher Account

- Username: `teacher1`
- Password: `teacher123`

### Student Account

- Username: `student1`
- Password: `student123`

## Next Steps

1. ✅ Run database migration
2. ✅ Create upload directories
3. ✅ Configure PHP limits
4. ✅ Test with each user role
5. ✅ Upload real resources
6. ✅ Share with users

## Support

If you encounter any issues:

1. Check the error log: `C:\xampp\apache\logs\error.log`
2. Verify all tables exist in database
3. Check file permissions on uploads directory
4. Ensure Apache and MySQL are running in XAMPP

## Success!

Your learning resources system is now ready to use! 🎉

- Teachers can upload resources
- Admins have full control
- Students can browse and download
- All downloads are tracked
- System is secure and responsive
