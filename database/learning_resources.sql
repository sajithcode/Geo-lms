-- Learning Resources Database Tables
-- This file creates tables for managing Notes, E-books, and Past Papers

-- Resource Categories Table
CREATE TABLE IF NOT EXISTS `resource_categories` (
  `category_id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_name` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notes Table
CREATE TABLE IF NOT EXISTS `notes` (
  `note_id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `file_path` VARCHAR(500) NOT NULL,
  `file_size` BIGINT NOT NULL,
  `category_id` INT DEFAULT NULL,
  `uploaded_by` INT NOT NULL,
  `download_count` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `resource_categories`(`category_id`) ON DELETE SET NULL,
  FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  INDEX `idx_category` (`category_id`),
  INDEX `idx_uploaded_by` (`uploaded_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- E-books Table
CREATE TABLE IF NOT EXISTS `ebooks` (
  `ebook_id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `author` VARCHAR(255),
  `description` TEXT,
  `file_path` VARCHAR(500) NOT NULL,
  `file_size` BIGINT NOT NULL,
  `category_id` INT DEFAULT NULL,
  `uploaded_by` INT NOT NULL,
  `download_count` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `resource_categories`(`category_id`) ON DELETE SET NULL,
  FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  INDEX `idx_category` (`category_id`),
  INDEX `idx_uploaded_by` (`uploaded_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Past Papers Table
CREATE TABLE IF NOT EXISTS `pastpapers` (
  `paper_id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `year` INT,
  `semester` VARCHAR(50),
  `description` TEXT,
  `file_path` VARCHAR(500) NOT NULL,
  `file_size` BIGINT NOT NULL,
  `category_id` INT DEFAULT NULL,
  `uploaded_by` INT NOT NULL,
  `download_count` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `resource_categories`(`category_id`) ON DELETE SET NULL,
  FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  INDEX `idx_year` (`year`),
  INDEX `idx_category` (`category_id`),
  INDEX `idx_uploaded_by` (`uploaded_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert some default categories
INSERT INTO `resource_categories` (`category_name`, `description`) VALUES
('Mathematics', 'Mathematical concepts and problem-solving'),
('Science', 'Physics, Chemistry, Biology resources'),
('Programming', 'Computer Science and programming resources'),
('Geography', 'GIS, Surveying, and Geomatics resources'),
('Engineering', 'Engineering principles and applications'),
('General', 'General educational resources')
ON DUPLICATE KEY UPDATE `category_name` = VALUES(`category_name`);

-- Create uploads directory structure (Note: This needs to be done at filesystem level)
-- mkdir -p ../uploads/notes
-- mkdir -p ../uploads/ebooks
-- mkdir -p ../uploads/pastpapers
