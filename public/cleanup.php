<?php
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/HypixelKeyManager.php';
require_once __DIR__ . '/../src/CacheManager.php';
require_once __DIR__ . '/../src/HypixelAPI.php';
require_once __DIR__ . '/../src/AdminAuth.php';

header('Content-Type: application/json');

try {
    AdminAuth::requireAuth();
    
    $cacheManager = new CacheManager();
    $api = new HypixelAPI();
    
    // Get statistics before cleanup
    $beforeStats = $cacheManager->getCacheStats();
    
    // Check if force parameter is set
    $useForce = isset($_GET['force']) || isset($_POST['force']);
    
    if ($useForce) {
        // Use force cleanup for more aggressive cleaning
        $cleanupResult = $cacheManager->forceCleanup();
        $api->keyManager->cleanupInvalidKeys(); // Also cleanup invalid keys
    } else {
        // Use normal cleanup
        $cleanupResult = $api->cleanupCache();
    }
    
    // Get statistics after cleanup
    $afterStats = $cacheManager->getCacheStats();
    
    echo json_encode([
        'success' => true,
        'cleanup_type' => $useForce ? 'force' : 'normal',
        'cleanup_time' => $cleanupResult['cleanup_time'],
        'before_cleanup' => $beforeStats,
        'cleanup_result' => $cleanupResult,
        'after_cleanup' => $afterStats,
        'summary' => [
            'expired_removed' => $cleanupResult['expired_removed'],
            'duplicates_removed' => $cleanupResult['duplicates_removed'],
            'total_removed' => $cleanupResult['total_cleaned'],
            'entries_before' => $beforeStats['total_entries'],
            'entries_after' => $afterStats['total_entries'],
            'space_saved' => $beforeStats['total_entries'] - $afterStats['total_entries']
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
} 