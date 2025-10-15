<?php
/**
 * Input Validation and Sanitization Helper
 * 
 * Provides functions for validating and sanitizing user input
 */

class Validator {
    
    private $errors = [];
    
    /**
     * Validate required field
     * 
     * @param string $value The value to validate
     * @param string $fieldName Field name for error message
     * @return bool True if valid
     */
    public function required($value, $fieldName = 'This field') {
        $value = trim($value);
        if (empty($value)) {
            $this->errors[] = "$fieldName is required.";
            return false;
        }
        return true;
    }
    
    /**
     * Validate email format
     * 
     * @param string $email Email to validate
     * @param string $fieldName Field name for error message
     * @return bool True if valid
     */
    public function email($email, $fieldName = 'Email') {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "$fieldName must be a valid email address.";
            return false;
        }
        return true;
    }
    
    /**
     * Validate minimum length
     * 
     * @param string $value Value to validate
     * @param int $min Minimum length
     * @param string $fieldName Field name for error message
     * @return bool True if valid
     */
    public function minLength($value, $min, $fieldName = 'This field') {
        if (strlen($value) < $min) {
            $this->errors[] = "$fieldName must be at least $min characters long.";
            return false;
        }
        return true;
    }
    
    /**
     * Validate maximum length
     * 
     * @param string $value Value to validate
     * @param int $max Maximum length
     * @param string $fieldName Field name for error message
     * @return bool True if valid
     */
    public function maxLength($value, $max, $fieldName = 'This field') {
        if (strlen($value) > $max) {
            $this->errors[] = "$fieldName must not exceed $max characters.";
            return false;
        }
        return true;
    }
    
    /**
     * Validate username format (alphanumeric, underscore, dash)
     * 
     * @param string $username Username to validate
     * @param string $fieldName Field name for error message
     * @return bool True if valid
     */
    public function username($username, $fieldName = 'Username') {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            $this->errors[] = "$fieldName can only contain letters, numbers, underscores, and dashes.";
            return false;
        }
        return true;
    }
    
    /**
     * Validate password strength
     * 
     * @param string $password Password to validate
     * @param string $fieldName Field name for error message
     * @param int $minLength Minimum password length
     * @return bool True if valid
     */
    public function password($password, $fieldName = 'Password', $minLength = 6) {
        if (strlen($password) < $minLength) {
            $this->errors[] = "$fieldName must be at least $minLength characters long.";
            return false;
        }
        return true;
    }
    
    /**
     * Validate password match
     * 
     * @param string $password Password
     * @param string $confirmPassword Confirm password
     * @return bool True if match
     */
    public function passwordMatch($password, $confirmPassword) {
        if ($password !== $confirmPassword) {
            $this->errors[] = "Passwords do not match.";
            return false;
        }
        return true;
    }
    
    /**
     * Validate numeric value
     * 
     * @param mixed $value Value to validate
     * @param string $fieldName Field name for error message
     * @return bool True if valid
     */
    public function numeric($value, $fieldName = 'This field') {
        if (!is_numeric($value)) {
            $this->errors[] = "$fieldName must be a number.";
            return false;
        }
        return true;
    }
    
    /**
     * Validate integer value
     * 
     * @param mixed $value Value to validate
     * @param string $fieldName Field name for error message
     * @return bool True if valid
     */
    public function integer($value, $fieldName = 'This field') {
        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            $this->errors[] = "$fieldName must be an integer.";
            return false;
        }
        return true;
    }
    
    /**
     * Validate value is within range
     * 
     * @param numeric $value Value to validate
     * @param numeric $min Minimum value
     * @param numeric $max Maximum value
     * @param string $fieldName Field name for error message
     * @return bool True if valid
     */
    public function range($value, $min, $max, $fieldName = 'This field') {
        if ($value < $min || $value > $max) {
            $this->errors[] = "$fieldName must be between $min and $max.";
            return false;
        }
        return true;
    }
    
    /**
     * Validate file upload
     * 
     * @param array $file $_FILES array element
     * @param array $allowedTypes Allowed MIME types
     * @param int $maxSize Maximum file size in bytes
     * @param string $fieldName Field name for error message
     * @return bool True if valid
     */
    public function file($file, $allowedTypes = [], $maxSize = 2097152, $fieldName = 'File') {
        // Check if file was uploaded
        if (!isset($file['error']) || is_array($file['error'])) {
            $this->errors[] = "$fieldName upload failed.";
            return false;
        }
        
        // Check for upload errors
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $this->errors[] = "$fieldName exceeds maximum size.";
                return false;
            case UPLOAD_ERR_NO_FILE:
                $this->errors[] = "$fieldName is required.";
                return false;
            default:
                $this->errors[] = "$fieldName upload error.";
                return false;
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            $maxSizeMB = round($maxSize / 1048576, 2);
            $this->errors[] = "$fieldName must not exceed {$maxSizeMB}MB.";
            return false;
        }
        
        // Check file type
        if (!empty($allowedTypes)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                $this->errors[] = "$fieldName type is not allowed.";
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate URL format
     * 
     * @param string $url URL to validate
     * @param string $fieldName Field name for error message
     * @return bool True if valid
     */
    public function url($url, $fieldName = 'URL') {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->errors[] = "$fieldName must be a valid URL.";
            return false;
        }
        return true;
    }
    
    /**
     * Validate date format
     * 
     * @param string $date Date string
     * @param string $format Date format (default: Y-m-d)
     * @param string $fieldName Field name for error message
     * @return bool True if valid
     */
    public function date($date, $format = 'Y-m-d', $fieldName = 'Date') {
        $d = DateTime::createFromFormat($format, $date);
        if (!$d || $d->format($format) !== $date) {
            $this->errors[] = "$fieldName must be a valid date in format $format.";
            return false;
        }
        return true;
    }
    
    /**
     * Get all validation errors
     * 
     * @return array Array of error messages
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Check if there are any errors
     * 
     * @return bool True if errors exist
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    /**
     * Clear all errors
     */
    public function clearErrors() {
        $this->errors = [];
    }
    
    /**
     * Sanitize string (remove HTML tags and encode special characters)
     * 
     * @param string $value String to sanitize
     * @return string Sanitized string
     */
    public static function sanitizeString($value) {
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize email
     * 
     * @param string $email Email to sanitize
     * @return string Sanitized email
     */
    public static function sanitizeEmail($email) {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }
    
    /**
     * Sanitize integer
     * 
     * @param mixed $value Value to sanitize
     * @return int Sanitized integer
     */
    public static function sanitizeInt($value) {
        return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }
    
    /**
     * Sanitize float
     * 
     * @param mixed $value Value to sanitize
     * @return float Sanitized float
     */
    public static function sanitizeFloat($value) {
        return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
    
    /**
     * Sanitize URL
     * 
     * @param string $url URL to sanitize
     * @return string Sanitized URL
     */
    public static function sanitizeUrl($url) {
        return filter_var(trim($url), FILTER_SANITIZE_URL);
    }
}
