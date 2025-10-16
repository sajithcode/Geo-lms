# Database Schema Fix - Learning Resources

## Issue

The Learning Resources Management system was showing this error:

```
Error fetching resources: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'n.category_id' in 'on clause'
```

## Root Cause

The resource tables (`notes`, `ebooks`, `pastpapers`) already existed in the database with a different schema than what the new code expected.

### Expected Schema (New Migration)

- Primary Key: `note_id`, `ebook_id`, `paper_id`
- File Path: `file_path`
- File Size: `file_size`
- Category: `category_id` (foreign key to `resource_categories` table)
- Downloads: `download_count`

### Actual Schema (Existing Tables)

- Primary Key: `id`
- File Path: `filepath`
- File Size: `filesize`
- Category: `category` (varchar - direct value)
- Downloads: `downloads`
- Additional Fields: `filename`, `file_type`

## Solution

Updated the following files to work with the existing database schema:

### 1. teacher/resources.php

**Changes:**

- Removed dependency on `resource_categories` table
- Used predefined category array instead
- Changed all SQL queries to use:
  - `id` instead of `note_id`, `ebook_id`, `paper_id`
  - `filepath` instead of `file_path`
  - `filesize` instead of `file_size`
  - `category` instead of `category_id`
  - `downloads` instead of `download_count`
- Added `filename` and `file_type` to INSERT queries
- For pastpapers, display `subject` field instead of `category`

### 2. php/download_resource.php

**Changes:**

- Changed ID field from `{resource_type}_id` to `id`
- Changed file path field from `file_path` to `filepath`
- Changed download counter field from `download_count` to `downloads`
- Removed `view_count` increment (column doesn't exist in current schema)

## Current Schema Details

### notes table

```sql
id - int(11) PRIMARY KEY
title - varchar(255)
description - text
filename - varchar(255)
filepath - varchar(500)
filesize - bigint(20)
file_type - varchar(50)
category - varchar(100)
uploaded_by - int(11)
downloads - int(11)
created_at - timestamp
```

### ebooks table

```sql
id - int(11) PRIMARY KEY
title - varchar(255)
author - varchar(255)
description - text
filename - varchar(255)
filepath - varchar(500)
filesize - bigint(20)
file_type - varchar(50)
category - varchar(100)
isbn - varchar(20)
uploaded_by - int(11)
downloads - int(11)
created_at - timestamp
updated_at - timestamp
```

### pastpapers table

```sql
id - int(11) PRIMARY KEY
title - varchar(255)
year - int(11)
semester - varchar(50)
subject - varchar(100)
description - text
filename - varchar(255)
filepath - varchar(500)
filesize - bigint(20)
file_type - varchar(50)
uploaded_by - int(11)
downloads - int(11)
created_at - timestamp
```

## Features Now Working

✅ **Teacher can upload resources** - All three types (notes, ebooks, pastpapers)
✅ **Category selection** - Uses predefined categories instead of database table
✅ **File validation** - Validates type and size (max 50MB)
✅ **Resource listing** - Shows all uploaded resources with statistics
✅ **Delete functionality** - Teachers can delete their resources
✅ **Download tracking** - Downloads counter increments correctly
✅ **Secure downloads** - Files served through PHP script

## Migration File Status

The `database/learning_resources.sql` migration file is **NOT NEEDED** since the tables already exist with a working schema. The system has been adapted to work with the existing database structure.

## Testing Checklist

- [x] Teacher upload form loads without errors
- [x] Can upload notes successfully
- [x] Can upload e-books with author field
- [x] Can upload past papers with year/semester
- [x] Resources display correctly in tabs
- [x] Download counters increment
- [x] Files download correctly
- [x] Delete functionality works
- [x] No SQL errors in error log

## Notes for Future Development

If you want to implement the new schema with foreign key relationships to a `resource_categories` table:

1. Backup existing data
2. Create new tables with the new schema
3. Migrate data from old tables to new tables
4. Update all queries to use new column names
5. Test thoroughly before going live

However, the current schema works well and doesn't require changes unless you need:

- Centralized category management
- Category descriptions
- Category-based permissions
- Category icons/images

## Conclusion

The Learning Resources system is now fully functional with the existing database schema. No migration is needed, and all features work correctly.
