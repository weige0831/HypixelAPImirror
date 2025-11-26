<?php
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/HypixelKeyManager.php';
require_once __DIR__ . '/../src/CacheManager.php';
require_once __DIR__ . '/../src/HypixelAPI.php';

header('Content-Type: application/json');

try {
    echo json_encode([
        'success' => true,
        'message' => 'All classes loaded successfully',
        'classes_loaded' => [
            'Database' => class_exists('Database'),
            'HypixelKeyManager' => class_exists('HypixelKeyManager'),
            'CacheManager' => class_exists('CacheManager'),
            'HypixelAPI' => class_exists('HypixelAPI')
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => $e->getFile()
    ]);
} 