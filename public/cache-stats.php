<?php
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/HypixelKeyManager.php';
require_once __DIR__ . '/../src/CacheManager.php';
require_once __DIR__ . '/../src/AdminAuth.php';

header('Content-Type: application/json');

try {
    AdminAuth::requireAuth();
    
    $cacheManager = new CacheManager();
    $db = Database::getInstance();
    
    // Get detailed cache statistics
    $stats = $cacheManager->getCacheStats();
    
    // Get cache age distribution
    $sql = "SELECT 
                CASE 
                    WHEN expires_at > NOW() THEN 'valid'
                    WHEN expires_at > DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 'expired_1day'
                    WHEN expires_at > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 'expired_1week'
                    ELSE 'expired_old'
                END as status,
                COUNT(*) as count
            FROM player_cache 
            GROUP BY status";
    $ageDistribution = $db->query($sql)->fetchAll();
    
    // Get recent cache activity
    $sql = "SELECT 
                DATE(created_at) as date,
                COUNT(*) as entries_created
            FROM player_cache 
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC";
    $recentActivity = $db->query($sql)->fetchAll();
    
    // Get most accessed players
    $sql = "SELECT 
                COALESCE(player_name, SUBSTRING(player_uuid, 1, 8)) as player,
                hit_count,
                last_hit,
                expires_at > NOW() as is_valid
            FROM player_cache 
            ORDER BY hit_count DESC 
            LIMIT 10";
    $topPlayers = $db->query($sql)->fetchAll();
    
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'basic_stats' => $stats,
        'age_distribution' => $ageDistribution,
        'recent_activity' => $recentActivity,
        'top_players' => $topPlayers,
        'recommendations' => [
            'should_cleanup' => $stats['expired_entries'] > 100,
            'has_duplicates' => $stats['duplicate_entries'] > 0,
            'cache_hit_ratio' => $stats['valid_entries'] / max($stats['total_entries'], 1)
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 