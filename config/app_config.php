<?php
// Application Configuration
class AppConfig {
    private static $baseUrl = null;
    private static $configLoaded = false;
    
    public static function loadConfig() {
        if (self::$configLoaded) {
            return;
        }
        
        // Load from .env file if exists
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                if (!array_key_exists($name, $_ENV)) {
                    $_ENV[$name] = $value;
                    putenv("$name=$value");
                }
            }
        }
        
        self::$configLoaded = true;
    }
    
    public static function getBaseUrl() {
        self::loadConfig();
        
        if (self::$baseUrl !== null) {
            return self::$baseUrl;
        }
        
        // Check for BASE_URL in environment
        $baseUrl = getenv('BASE_URL');
        if ($baseUrl) {
            self::$baseUrl = rtrim($baseUrl, '/');
            return self::$baseUrl;
        }
        
        // Auto-detect from server
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = dirname($_SERVER['SCRIPT_NAME']);
        
        // Remove common patterns
        $path = str_replace('/lgu', '', $path);
        $path = str_replace('/static_page/public', '', $path);
        $path = str_replace('/static_page', '', $path);
        $path = rtrim($path, '/');
        
        self::$baseUrl = "$protocol://$host$path";
        return self::$baseUrl;
    }
    
    public static function asset($path) {
        return self::getBaseUrl() . '/' . ltrim($path, '/');
    }
    
    public static function url($path) {
        return self::getBaseUrl() . '/' . ltrim($path, '/');
    }
    
    public static function uploads($path) {
        return self::getBaseUrl() . '/lgu/uploads/' . ltrim($path, '/');
    }
    
    public static function isHttps() {
        return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    }
}

// Auto-load config
AppConfig::loadConfig();
?>
