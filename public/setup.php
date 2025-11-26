<?php
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/HypixelKeyManager.php';
require_once __DIR__ . '/../src/CacheManager.php';
require_once __DIR__ . '/../src/HypixelAPI.php';
require_once __DIR__ . '/../src/AdminAuth.php';

header('Content-Type: application/json');

// Check if database tables exist
function checkTablesExist($db) {
    $tables = ['hypixel_api_keys', 'player_cache', 'request_logs'];
    $existing = [];
    
    foreach ($tables as $table) {
        $sql = "SHOW TABLES LIKE ?";
        $result = $db->query($sql, [$table])->fetch();
        $existing[$table] = $result !== false;
    }
    
    return $existing;
}

// Get basic system info
function getSystemInfo() {
    return [
        'php_version' => PHP_VERSION,
        'server_time' => date('Y-m-d H:i:s'),
        'timezone' => date_default_timezone_get(),
        'extensions' => [
            'pdo' => extension_loaded('pdo'),
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            'curl' => extension_loaded('curl'),
            'json' => extension_loaded('json')
        ]
    ];
}

try {
    AdminAuth::requireAuth();
    
    $db = Database::getInstance();
    $response = [
        'success' => true,
        'setup_time' => date('Y-m-d H:i:s'),
        'steps' => []
    ];
    
    // Step 1: Check system requirements
    $systemInfo = getSystemInfo();
    $response['steps'][] = [
        'step' => 'system_check',
        'status' => 'success',
        'message' => 'System requirements checked',
        'data' => $systemInfo
    ];
    
    // Step 2: Check existing tables
    $existingTables = checkTablesExist($db);
    $allTablesExist = !in_array(false, $existingTables);
    
    $response['steps'][] = [
        'step' => 'table_check',
        'status' => 'success',
        'message' => $allTablesExist ? 'All tables exist' : 'Some tables missing',
        'data' => $existingTables
    ];
    
    // Step 3: Create/Update database tables if needed
    if (!$allTablesExist) {
        try {
    $sql = file_get_contents(__DIR__ . '/../database.sql');
    $db->getConnection()->exec($sql);
            $response['steps'][] = [
                'step' => 'create_tables',
                'status' => 'success',
                'message' => 'Database tables created/updated successfully'
            ];
        } catch (Exception $e) {
            $response['steps'][] = [
                'step' => 'create_tables',
                'status' => 'error',
                'message' => 'Failed to create/update tables: ' . $e->getMessage()
            ];
        }
    } else {
        $response['steps'][] = [
            'step' => 'create_tables',
            'status' => 'skipped',
            'message' => 'All tables already exist'
        ];
    }
    
    // Step 4: Check API keys
    try {
        $sql = "SELECT COUNT(*) as count FROM hypixel_api_keys WHERE is_valid = 1";
        $result = $db->query($sql)->fetch();
        $validKeys = (int)$result['count'];
        
        $response['steps'][] = [
            'step' => 'api_keys_check',
            'status' => 'success',
            'message' => "Found {$validKeys} valid API keys",
            'data' => ['valid_keys' => $validKeys]
        ];
        
        if ($validKeys === 0) {
            $response['steps'][] = [
                'step' => 'api_keys_warning',
                'status' => 'warning',
                'message' => 'No valid API keys found. Add keys via /hypixel-keys.php'
            ];
        }
    } catch (Exception $e) {
        $response['steps'][] = [
            'step' => 'api_keys_check',
            'status' => 'error',
            'message' => 'Failed to check API keys: ' . $e->getMessage()
        ];
    }
    
    // Step 5: Basic configuration check
    try {
        $config = require __DIR__ . '/../config.php';
        $configChecks = [
            'database_configured' => !empty($config['database']['dbname']),
            'admin_configured' => !empty($config['admin']['username']),
            'cache_duration_set' => isset($config['hypixel']['cache_duration'])
        ];
        
        $response['steps'][] = [
            'step' => 'config_check',
            'status' => 'success',
            'message' => 'Configuration checked',
            'data' => $configChecks
        ];
    } catch (Exception $e) {
        $response['steps'][] = [
            'step' => 'config_check',
            'status' => 'error',
            'message' => 'Configuration check failed: ' . $e->getMessage()
        ];
    }
    
    // Final status
    $hasErrors = false;
    foreach ($response['steps'] as $step) {
        if ($step['status'] === 'error') {
            $hasErrors = true;
            break;
        }
    }
    
    if (!$hasErrors) {
        $response['message'] = '✅ Setup completed successfully! The Hypixel Mirror API is ready to use.';
        $response['next_steps'] = [
            '1. Add Hypixel API keys via /hypixel-keys.php',
            '2. Test the API via /index.php?uuid=player_uuid',
            '3. Monitor statistics via /stats.php',
            '4. Use /cleanup.php for cache maintenance'
        ];
    } else {
        $response['message'] = '⚠️ Setup completed with errors. Please check the steps above.';
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
} 