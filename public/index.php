<?php
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/HypixelKeyManager.php';
require_once __DIR__ . '/../src/CacheManager.php';
require_once __DIR__ . '/../src/HypixelAPI.php';

header('Content-Type: application/json');

try {
    // Check for either UUID or username
    $identifier = null;
    if (isset($_GET['uuid'])) {
        $identifier = $_GET['uuid'];
    } elseif (isset($_GET['name'])) {
        $identifier = $_GET['name'];
    } else {
        throw new Exception('Either player UUID or name is required');
    }
    
    $api = new HypixelAPI();
    $data = $api->getPlayer($identifier);
    
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 