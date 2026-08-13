<?php
// Directory Management and Validation
class DirectoryManager {
    private static $directories = [
        'lgu/uploads/procurement',
        'lgu/uploads/news',
        'lgu/uploads/scholarship',
        'lgu/uploads/employees',
        'storage/logs',
        'storage/rate_limits',
        'storage/login_attempts'
    ];
    
    public static function ensureDirectories() {
        $baseDir = __DIR__ . '/..';
        
        foreach (self::$directories as $dir) {
            $fullPath = $baseDir . '/' . $dir;
            
            if (!is_dir($fullPath)) {
                if (!mkdir($fullPath, 0755, true)) {
                    error_log("Failed to create directory: $fullPath");
                    return false;
                }
                
                // Create .htaccess to prevent direct access for storage directories
                if (strpos($dir, 'storage') === 0) {
                    $htaccess = $fullPath . '/.htaccess';
                    if (!file_exists($htaccess)) {
                        file_put_contents($htaccess, "Deny from all\n");
                    }
                }
            }
            
            // Check if directory is writable
            if (!is_writable($fullPath)) {
                error_log("Directory not writable: $fullPath");
                return false;
            }
        }
        
        return true;
    }
    
    public static function validateDirectory($path) {
        $fullPath = __DIR__ . '/../' . $path;
        
        if (!is_dir($fullPath)) {
            return ['valid' => false, 'error' => 'Directory does not exist'];
        }
        
        if (!is_readable($fullPath)) {
            return ['valid' => false, 'error' => 'Directory is not readable'];
        }
        
        if (!is_writable($fullPath)) {
            return ['valid' => false, 'error' => 'Directory is not writable'];
        }
        
        return ['valid' => true];
    }
    
    public static function getDirectoryStatus() {
        $status = [];
        $baseDir = __DIR__ . '/..';
        
        foreach (self::$directories as $dir) {
            $fullPath = $baseDir . '/' . $dir;
            $validation = self::validateDirectory($dir);
            $status[$dir] = $validation;
        }
        
        return $status;
    }
}

// Auto-ensure directories on load
DirectoryManager::ensureDirectories();
?>
