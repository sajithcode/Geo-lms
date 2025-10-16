-- Add status column to feedbacks table for teacher feedback management
-- This migration adds a status column to track feedback lifecycle

-- Check if feedbacks table exists and add status column if it doesn't have one
ALTER TABLE feedbacks 
ADD COLUMN IF NOT EXISTS status ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending' AFTER message;

-- Add index for faster filtering by status
CREATE INDEX IF NOT EXISTS idx_feedback_status ON feedbacks(status);

-- Add updated_at column if it doesn't exist
ALTER TABLE feedbacks 
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;
