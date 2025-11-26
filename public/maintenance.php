<?php
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/HypixelKeyManager.php';
require_once __DIR__ . '/../src/CacheManager.php';
require_once __DIR__ . '/../src/HypixelAPI.php';
require_once __DIR__ . '/../src/AdminAuth.php';

header('Content-Type: application/json');

// Check for duplicate cache entries
function checkDuplicates($db) {
    $results = [];
    
    // Check UUID duplicates
    $sql = "SELECT COUNT(*) as count FROM (
                SELECT player_uuid FROM player_cache 
                WHERE player_uuid IS NOT NULL 
                GROUP BY player_uuid 
                HAVING COUNT(*) > 1
            ) as uuid_dups";
    $result = $db->query($sql)->fetch();
    $results['uuid_duplicates'] = (int)$result['count'];
    
    // Check name duplicates
    $sql = "SELECT COUNT(*) as count FROM (
                SELECT player_name FROM player_cache 
                WHERE player_name IS NOT NULL 
                GROUP BY player_name 
                HAVING COUNT(*) > 1
            ) as name_dups";
    $result = $db->query($sql)->fetch();
    $results['name_duplicates'] = (int)$result['count'];
    
    return $results;
}

// Remove duplicate cache entries
function removeDuplicates($db) {
    $removed = 0;
    
    // Remove duplicate UUID entries, keeping the most recent
    $sql = "DELETE pc1 FROM player_cache pc1
            INNER JOIN player_cache pc2 
            WHERE pc1.id < pc2.id 
            AND pc1.player_uuid IS NOT NULL 
            AND pc1.player_uuid = pc2.player_uuid";
    $stmt = $db->query($sql);
    $removed += $stmt->rowCount();
    
    // Remove duplicate name entries, keeping the most recent
    $sql = "DELETE pc1 FROM player_cache pc1
            INNER JOIN player_cache pc2 
            WHERE pc1.id < pc2.id 
            AND pc1.player_name IS NOT NULL 
            AND pc1.player_name = pc2.player_name";
    $stmt = $db->query($sql);
    $removed += $stmt->rowCount();
    
    return $removed;
}

try {
    AdminAuth::requireAuth();
    
    $db = Database::getInstance();
    $cacheManager = new CacheManager();
    $action = $_GET['action'] ?? 'status';
    
    $response = [
        'success' => true,
        'action' => $action,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    switch ($action) {
        case 'status':
            // Get detailed cache status
            $stats = $cacheManager->getCacheStats();
            $duplicates = checkDuplicates($db);
            
            $response['data'] = [
                'cache_stats' => $stats,
                'duplicates' => $duplicates,
                'total_issues' => $stats['expired_entries'] + $duplicates['uuid_duplicates'] + $duplicates['name_duplicates']
            ];
            break;
            
        case 'cleanup':
            // Standard cleanup
            $beforeStats = $cacheManager->getCacheStats();
            $cleanupResult = $cacheManager->cleanup();
            $afterStats = $cacheManager->getCacheStats();
            
            $response['data'] = [
                'before' => $beforeStats,
                'result' => $cleanupResult,
                'after' => $afterStats
            ];
            break;
            
        case 'force_cleanup':
            // Force cleanup (more aggressive)
            $beforeStats = $cacheManager->getCacheStats();
            $cleanupResult = $cacheManager->forceCleanup();
            $afterStats = $cacheManager->getCacheStats();
            
            $response['data'] = [
                'before' => $beforeStats,
                'result' => $cleanupResult,
                'after' => $afterStats
            ];
            break;
            
        case 'remove_duplicates':
            // Remove duplicate entries only
            $beforeDuplicates = checkDuplicates($db);
            $removed = removeDuplicates($db);
            $afterDuplicates = checkDuplicates($db);
            
            $response['data'] = [
                'before' => $beforeDuplicates,
                'removed' => $removed,
                'after' => $afterDuplicates
            ];
            break;
            
        case 'full_maintenance':
            // Complete maintenance: duplicates + cleanup
            $beforeStats = $cacheManager->getCacheStats();
            $beforeDuplicates = checkDuplicates($db);
            
            // Step 1: Remove duplicates
            $duplicatesRemoved = removeDuplicates($db);
            
            // Step 2: Force cleanup
            $cleanupResult = $cacheManager->forceCleanup();
            
            $afterStats = $cacheManager->getCacheStats();
            $afterDuplicates = checkDuplicates($db);
            
            $response['data'] = [
                'before' => [
                    'stats' => $beforeStats,
                    'duplicates' => $beforeDuplicates
                ],
                'duplicates_removed' => $duplicatesRemoved,
                'cleanup_result' => $cleanupResult,
                'after' => [
                    'stats' => $afterStats,
                    'duplicates' => $afterDuplicates
                ]
            ];
            break;
            
        default:
            throw new Exception('Invalid action. Valid actions: status, cleanup, force_cleanup, remove_duplicates, full_maintenance');
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 