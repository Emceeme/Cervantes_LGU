<?php
/**
 * Upload Helper Class
 * 
 * Handles file upload paths and operations consistently across the application
 * Works on both local development and Render deployment
 */

class UploadHelper {
    
    /**
     * Get the base upload directory path
     * Returns absolute path to lgu/uploads/
     */
    public static function getUploadBaseDir() {
        // In Docker/Render, the base is /var/www/html
        // In local dev, it's the project root
        $projectRoot = defined('PROJECT_ROOT') ? PROJECT_ROOT : __DIR__ . '/..';
        return $projectRoot . '/lgu/uploads';
    }
    
    /**
     * Get upload directory for a specific type
     * 
     * @param string $type - 'news', 'scholarship', 'resumes', 'procurement'
     * @return string Absolute path to the upload directory
     */
    public static function getUploadDir($type) {
        $baseDir = self::getUploadBaseDir();
        $dir = $baseDir . '/' . $type;
        
        // Create directory if it doesn't exist
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        return $dir;
    }
    
    /**
     * Upload a file to the specified directory
     * 
     * @param array $file - $_FILES array element
     * @param string $type - 'news', 'scholarship', 'resumes', 'procurement'
     * @param string $filename - Optional custom filename
     * @return string|false - Filename on success, false on failure
     */
    public static function uploadFile($file, $type, $filename = null) {
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return false;
        }
        
        $uploadDir = self::getUploadDir($type);
        
        // Generate filename if not provided
        if ($filename === null) {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        }
        
        $destination = $uploadDir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $filename;
        }
        
        return false;
    }
    
    /**
     * Delete a file from the specified directory
     * 
     * @param string $filename - Filename to delete
     * @param string $type - 'news', 'scholarship', 'resumes', 'procurement'
     * @return bool
     */
    public static function deleteFile($filename, $type) {
        $uploadDir = self::getUploadDir($type);
        $filepath = $uploadDir . '/' . $filename;
        
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        
        return false;
    }
    
    /**
     * Check if a file exists
     * 
     * @param string $filename - Filename to check
     * @param string $type - 'news', 'scholarship', 'resumes', 'procurement'
     * @return bool
     */
    public static function fileExists($filename, $type) {
        $uploadDir = self::getUploadDir($type);
        $filepath = $uploadDir . '/' . $filename;
        return file_exists($filepath);
    }
}
?>
