<?php
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/HypixelKeyManager.php';
require_once __DIR__ . '/../src/CacheManager.php';
require_once __DIR__ . '/../src/HypixelAPI.php';
require_once __DIR__ . '/../src/AdminAuth.php';

header('Content-Type: application/json');

try {
    AdminAuth::requireAuth();
    
    $api = new HypixelAPI();
    
    // Handle different HTTP methods
    if (isset($_GET['add_key'])) {
        // Add new Hypixel API key via GET
        $api->addHypixelKey($_GET['add_key']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Hypixel API key added successfully'
        ]);
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Add new Hypixel API key via POST
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['hypixel_key']) || empty(trim($data['hypixel_key']))) {
            throw new Exception('Hypixel API key is required and cannot be empty');
        }
        
        $api->addHypixelKey($data['hypixel_key']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Hypixel API key added successfully'
        ]);
    } else {
        // Get Hypixel API keys status
        $stats = $api->getHypixelKeyStats();
        
        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 