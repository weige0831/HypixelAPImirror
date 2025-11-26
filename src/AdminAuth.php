<?php

class AdminAuth {
    private static $config = null;
    
    private static function getConfig() {
        if (self::$config === null) {
            self::$config = require __DIR__ . '/../config.php';
        }
        return self::$config;
    }
    
    /**
     * Authenticate admin user
     * @return bool
     */
    public static function authenticate() {
        $config = self::getConfig();
        
        if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
            return false;
        }
        
        return $_SERVER['PHP_AUTH_USER'] === $config['admin']['username'] &&
               $_SERVER['PHP_AUTH_PW'] === $config['admin']['password'];
    }
    
    /**
     * Require authentication or send 401 response
     * @param string $realm
     */
    public static function requireAuth($realm = 'Admin Access') {
        if (!self::authenticate()) {
            header('WWW-Authenticate: Basic realm="' . $realm . '"');
            header('HTTP/1.0 401 Unauthorized');
            echo json_encode([
                'success' => false,
                'error' => 'Authentication required'
            ]);
            exit;
        }
    }
} 