<?php
class CacheManager {
    private $db;
    private $config;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->config = require __DIR__ . '/../config.php';
    }
    
    public function getPlayerData($identifier) {
        $currentTime = date('Y-m-d H:i:s');
        
        // Try UUID first (UUIDs are typically 32 characters without hyphens, or 36 with hyphens)
        if (strlen($identifier) >= 32) {
            // First check if there's any cache entry for this UUID (including expired ones)
            $sql = "SELECT id, player_data, created_at, expires_at FROM player_cache 
                    WHERE player_uuid = ? ORDER BY created_at DESC LIMIT 1";
            $result = $this->db->query($sql, [$identifier])->fetch();
            
            if ($result) {
                // Check if cache is expired
                if ($result['expires_at'] <= $currentTime) {
                    // Cache is expired, delete it immediately
                    $sql = "DELETE FROM player_cache WHERE id = ?";
                    $this->db->query($sql, [$result['id']]);
                    return null; // Return null so API layer will fetch fresh data
                }
                
                // Cache is valid, update hit count and return data
                $sql = "UPDATE player_cache 
                        SET hit_count = hit_count + 1, last_hit = NOW()
                        WHERE id = ?";
                $this->db->query($sql, [$result['id']]);
                
                // Log cache hit
                $sql = "INSERT INTO request_logs 
                        (hypixel_key_id, endpoint, response_code, request_type) 
                        VALUES (NULL, '/player', 200, 'CACHE')";
                $this->db->query($sql);
                
                // Return the cached data
                $data = json_decode($result['player_data'], true);
                $data['fetch_time'] = $result['created_at'];
                return $data;
            }
        } else {
            // Try username (for shorter identifiers)
            $sql = "SELECT id, player_data, created_at, expires_at FROM player_cache 
                    WHERE player_name = ? ORDER BY created_at DESC LIMIT 1";
            $result = $this->db->query($sql, [$identifier])->fetch();
            
            if ($result) {
                // Check if cache is expired
                if ($result['expires_at'] <= $currentTime) {
                    // Cache is expired, delete it immediately
                    $sql = "DELETE FROM player_cache WHERE id = ?";
                    $this->db->query($sql, [$result['id']]);
                    return null; // Return null so API layer will fetch fresh data
                }
                
                // Cache is valid, update hit count and return data
                $sql = "UPDATE player_cache 
                        SET hit_count = hit_count + 1, last_hit = NOW()
                        WHERE id = ?";
                $this->db->query($sql, [$result['id']]);
                
                // Log cache hit
                $sql = "INSERT INTO request_logs 
                        (hypixel_key_id, endpoint, response_code, request_type) 
                        VALUES (NULL, '/player', 200, 'CACHE')";
                $this->db->query($sql);
        
                // Return the cached data
            $data = json_decode($result['player_data'], true);
            $data['fetch_time'] = $result['created_at'];
            return $data;
            }
        }
        
        // No cache found
        return null;
    }
    
    public function cachePlayerData($data, $hypixelKeyId) {
        if (!isset($data['player']) || !isset($data['player']['uuid'])) {
            throw new Exception('Invalid player data format');
        }
        
        $player = $data['player'];
        $expiresAt = date('Y-m-d H:i:s', time() + $this->config['hypixel']['cache_duration']);
        $currentTime = date('Y-m-d H:i:s');
        
        // Add fetch time to the data
        $data['fetch_time'] = $currentTime;
        
        // First, delete any existing cache for this player to prevent duplicates
        $this->deletePlayerCache($player['uuid'], $player['displayname'] ?? null);
        
        // Occasionally run batch cleanup for expired entries
        $this->batchCleanupIfNeeded();
        
        // Insert new cache entry
        $sql = "INSERT INTO player_cache 
                (player_uuid, player_name, player_data, expires_at, hypixel_key_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?)";
                
        $this->db->query($sql, [
            $player['uuid'],
            $player['displayname'] ?? null,
            json_encode($data),
            $expiresAt,
            $hypixelKeyId,
            $currentTime
        ]);
        
        return $data;
    }
    
    /**
     * Delete existing cache entries for a player to prevent duplicates
     */
    private function deletePlayerCache($uuid, $name = null) {
        // Delete by UUID
        if ($uuid) {
            $sql = "DELETE FROM player_cache WHERE player_uuid = ?";
            $this->db->query($sql, [$uuid]);
        }
        
        // Delete by name if provided
        if ($name) {
            $sql = "DELETE FROM player_cache WHERE player_name = ?";
            $this->db->query($sql, [$name]);
        }
    }
    
    public function cleanup() {
        $cleanedExpired = 0;
        $cleanedDuplicates = 0;
        
        // Remove expired cache entries with more precise time comparison
        $currentTime = date('Y-m-d H:i:s');
        $sql = "DELETE FROM player_cache WHERE expires_at < ?";
        $stmt = $this->db->query($sql, [$currentTime]);
        $cleanedExpired = $stmt->rowCount();
        
        // Also clean up any potential duplicate entries (safety measure)
        $cleanedDuplicates = $this->cleanupDuplicates();
        
        // Log the cleanup activity if anything was cleaned
        if ($cleanedExpired > 0 || $cleanedDuplicates > 0) {
            error_log("Cache cleanup: removed {$cleanedExpired} expired entries and {$cleanedDuplicates} duplicates at {$currentTime}");
        }
        
        return [
            'expired_removed' => $cleanedExpired,
            'duplicates_removed' => $cleanedDuplicates,
            'total_cleaned' => $cleanedExpired + $cleanedDuplicates,
            'cleanup_time' => $currentTime
        ];
    }
    
    /**
     * Clean up duplicate cache entries, keeping only the most recent one
     */
    private function cleanupDuplicates() {
        $totalRemoved = 0;
        
        // Remove duplicate UUID entries, keeping the most recent
        $sql = "DELETE pc1 FROM player_cache pc1
                INNER JOIN player_cache pc2 
                WHERE pc1.id < pc2.id 
                AND pc1.player_uuid IS NOT NULL 
                AND pc1.player_uuid = pc2.player_uuid";
        $stmt = $this->db->query($sql);
        $totalRemoved += $stmt->rowCount();
        
        // Remove duplicate name entries, keeping the most recent
        $sql = "DELETE pc1 FROM player_cache pc1
                INNER JOIN player_cache pc2 
                WHERE pc1.id < pc2.id 
                AND pc1.player_name IS NOT NULL 
                AND pc1.player_name = pc2.player_name";
        $stmt = $this->db->query($sql);
        $totalRemoved += $stmt->rowCount();
        
        return $totalRemoved;
    }
    
    /**
     * Batch cleanup expired entries - only run when there are many expired entries
     * This is a backup cleanup mechanism for bulk expired data
     */
    public function batchCleanupIfNeeded() {
        // Only check periodically (1 in 100 requests)
        if (mt_rand(1, 100) !== 1) {
            return;
        }
        
        // Get quick count of expired entries
        $sql = "SELECT COUNT(*) as expired FROM player_cache WHERE expires_at < NOW()";
        $result = $this->db->query($sql)->fetch();
        $expiredCount = (int)$result['expired'];
        
        // Only run batch cleanup if there are many expired entries
        if ($expiredCount > 50) {
            $cleanupResult = $this->cleanup();
            if ($cleanupResult['total_cleaned'] > 0) {
                error_log("Batch cleanup triggered: removed {$cleanupResult['total_cleaned']} entries (expired: {$expiredCount})");
            }
        }
    }
    
    /**
     * Get cache statistics
     */
    public function getCacheStats() {
        $stats = [];
        
        // Total entries
        $sql = "SELECT COUNT(*) as total FROM player_cache";
        $result = $this->db->query($sql)->fetch();
        $stats['total_entries'] = (int)$result['total'];
        
        // Valid entries
        $sql = "SELECT COUNT(*) as valid FROM player_cache WHERE expires_at > NOW()";
        $result = $this->db->query($sql)->fetch();
        $stats['valid_entries'] = (int)$result['valid'];
        
        // Expired entries
        $stats['expired_entries'] = $stats['total_entries'] - $stats['valid_entries'];
        
        // Check for duplicates
        $sql = "SELECT COUNT(*) as duplicates FROM (
                    SELECT player_uuid FROM player_cache 
                    WHERE player_uuid IS NOT NULL 
                    GROUP BY player_uuid HAVING COUNT(*) > 1
                    UNION ALL
                    SELECT player_name FROM player_cache 
                    WHERE player_name IS NOT NULL 
                    GROUP BY player_name HAVING COUNT(*) > 1
                ) as dups";
        $result = $this->db->query($sql)->fetch();
        $stats['duplicate_entries'] = (int)$result['duplicates'];
        
        return $stats;
    }
    
    /**
     * Force cleanup all expired entries (more aggressive cleanup for testing)
     */
    public function forceCleanup() {
        $cleanedExpired = 0;
        $cleanedDuplicates = 0;
        $currentTime = date('Y-m-d H:i:s');
        
        // Get count before cleanup for verification
        $sql = "SELECT COUNT(*) as count FROM player_cache WHERE expires_at < ?";
        $beforeCount = $this->db->query($sql, [$currentTime])->fetch();
        
        // Remove expired cache entries (using < instead of <=)
        $sql = "DELETE FROM player_cache WHERE expires_at < ?";
        $stmt = $this->db->query($sql, [$currentTime]);
        $cleanedExpired = $stmt->rowCount();
        
        // Also clean entries that are exactly at expiration time
        $sql = "DELETE FROM player_cache WHERE expires_at = ?";
        $stmt = $this->db->query($sql, [$currentTime]);
        $cleanedExpired += $stmt->rowCount();
        
        // Clean up duplicates
        $cleanedDuplicates = $this->cleanupDuplicates();
        
        // Get count after cleanup for verification
        $sql = "SELECT COUNT(*) as count FROM player_cache WHERE expires_at < ?";
        $afterCount = $this->db->query($sql, [$currentTime])->fetch();
        
        error_log("Force cleanup: found {$beforeCount['count']} expired, removed {$cleanedExpired}, remaining {$afterCount['count']} at {$currentTime}");
        
        return [
            'expired_found' => (int)$beforeCount['count'],
            'expired_removed' => $cleanedExpired,
            'duplicates_removed' => $cleanedDuplicates,
            'total_cleaned' => $cleanedExpired + $cleanedDuplicates,
            'remaining_expired' => (int)$afterCount['count'],
            'cleanup_time' => $currentTime
        ];
    }
} 