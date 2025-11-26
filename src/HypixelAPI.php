<?php
class HypixelAPI {
    private $config;
    private $db;
    private $keyManager;
    private $cache;
    
    public function __construct() {
        $this->config = require __DIR__ . '/../config.php';
        $this->db = Database::getInstance();
        $this->keyManager = new HypixelKeyManager();
        $this->cache = new CacheManager();
    }
    
    private function logRequest($keyId, $endpoint, $responseCode) {
        // Log the request
        $sql = "INSERT INTO request_logs 
                (hypixel_key_id, endpoint, response_code, request_type) 
                VALUES (?, ?, ?, 'API')";
        $this->db->query($sql, [$keyId, $endpoint, $responseCode]);
        
        // Update key's request counts
        $sql = "UPDATE hypixel_api_keys 
                SET daily_requests = daily_requests + 1,
                    total_requests = total_requests + 1
                WHERE id = ?";
        $this->db->query($sql, [$keyId]);
    }
    
    public function getRequestStats() {
        // Get current time in server timezone
        $currentTime = date('Y-m-d H:i:s');
        
        // Get available API keys count
        $sql = "SELECT 
                COUNT(*) as total_keys,
                SUM(CASE WHEN is_valid = 1 THEN 1 ELSE 0 END) as valid_keys
                FROM hypixel_api_keys";
        $keyStats = $this->db->query($sql)->fetch();
        
        // Get today's total requests, API requests and cache hits
        $sql = "SELECT 
                COUNT(*) as total_requests,
                COUNT(CASE WHEN response_code = 200 THEN 1 END) as successful_requests,
                COUNT(CASE WHEN response_code = 429 THEN 1 END) as rate_limited_requests,
                SUM(CASE WHEN request_type = 'CACHE' THEN 1 ELSE 0 END) as cache_hits
                FROM request_logs 
                WHERE DATE(request_time) = CURRENT_DATE()";
        $todayStats = $this->db->query($sql)->fetch();
        
        // Get last request time
        $sql = "SELECT request_time 
                FROM request_logs 
                ORDER BY request_time DESC 
                LIMIT 1";
        $lastRequest = $this->db->query($sql)->fetch();
        
        // Get per-key statistics for today
        $sql = "SELECT 
                hak.api_key,
                hak.owner,
                hak.is_valid,
                hak.daily_requests as requests_today,
                hak.total_requests,
                hak.last_used as last_request
                FROM hypixel_api_keys hak
                ORDER BY hak.is_valid DESC, hak.daily_requests ASC";
        
        $keyDetails = $this->db->query($sql)->fetchAll();
        
        // Get basic cache statistics
        $sql = "SELECT COUNT(*) as total FROM player_cache";
        $totalCache = $this->db->query($sql)->fetch();
        
        $sql = "SELECT COUNT(*) as valid FROM player_cache WHERE expires_at > NOW()";
        $validCache = $this->db->query($sql)->fetch();
        
        $cacheStats = [
            'total_entries' => (int)$totalCache['total'],
            'valid_entries' => (int)$validCache['valid'],
            'expired_entries' => (int)$totalCache['total'] - (int)$validCache['valid']
        ];
        
        return [
            'timestamp' => $currentTime,
            'date' => date('Y-m-d'),
            'api_keys' => [
                'total' => (int)$keyStats['total_keys'],
                'valid' => (int)$keyStats['valid_keys']
            ],
            'today_stats' => [
                'api_requests' => (int)$todayStats['total_requests'] - (int)$todayStats['cache_hits'],
                'cache_hits' => (int)$todayStats['cache_hits'],
                'successful_requests' => (int)$todayStats['successful_requests'],
                'failed_requests' => (int)$todayStats['total_requests'] - (int)$todayStats['successful_requests'],
                'unique_players' => $this->getUniquePlayersToday(),
                'total_requests' => (int)$todayStats['total_requests'],
                'last_updated' => $currentTime
            ],
            'cache_stats' => $cacheStats,
            'last_request' => $lastRequest ? $lastRequest['request_time'] : null,
            'keys_detail' => array_map(function($key) {
                return [
                    'key' => substr($key['api_key'], 0, 8) . '...' . substr($key['api_key'], -4),
                    'owner' => $key['owner'],
                    'requests_today' => (int)$key['requests_today'],
                    'total_requests' => (int)$key['total_requests'],
                    'last_request' => $key['last_request'],
                    'is_valid' => (bool)$key['is_valid']
                ];
            }, $keyDetails)
        ];
    }
    
    private function getUniquePlayersToday() {
        $sql = "SELECT COUNT(DISTINCT 
                    CASE 
                        WHEN player_uuid IS NOT NULL THEN player_uuid 
                        ELSE player_name 
                    END
                ) as unique_players
                FROM player_cache 
                WHERE DATE(created_at) = CURRENT_DATE()";
        
        $result = $this->db->query($sql)->fetch();
        return (int)$result['unique_players'];
    }
    
    private function fetchFromHypixel($endpoint, $params) {
        // Get a valid Hypixel API key
        $hypixelKey = $this->keyManager->getValidKey();
        
        $url = $this->config['hypixel']['base_url'] . $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'API-Key: ' . $hypixelKey['api_key']
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Log the request
        $this->logRequest($hypixelKey['id'], $endpoint, $httpCode);
        
        // Parse response
        $data = json_decode($response, true);
        
        // Handle different response cases
        if ($httpCode === 200) {
            if (isset($data['success']) && $data['success'] === false) {
                if (isset($data['cause'])) {
                    switch ($data['cause']) {
                        case 'Invalid API key':
                            // Delete the invalid key
                            $sql = "DELETE FROM hypixel_api_keys WHERE id = ?";
                            $this->db->query($sql, [$hypixelKey['id']]);
                            throw new Exception('Invalid API key, key has been removed');
                            
                        case 'You have already looked up this name recently':
                            // This is a rate limit message, not an error with the key
                            throw new Exception($data['cause']);
                            
                        default:
                            // Other API errors
                            throw new Exception($data['cause']);
                    }
                }
                throw new Exception('Hypixel API request failed');
            }
            
            // Update key status for successful request
            $this->keyManager->updateKeyStatus($hypixelKey['id'], $httpCode);
            return ['data' => $data, 'key_id' => $hypixelKey['id']];
        }
        
        // Handle HTTP error codes
        switch ($httpCode) {
            case 403:
                // Delete the key if we get a 403 (Forbidden)
                $sql = "DELETE FROM hypixel_api_keys WHERE id = ?";
                $this->db->query($sql, [$hypixelKey['id']]);
                throw new Exception('Invalid API key, key has been removed');
                
            case 429:
                throw new Exception('Too many requests, please try again later');
                
            case 400:
                throw new Exception('Invalid request parameters');
                
            default:
                throw new Exception('Internal server error');
        }
    }
    
    public function getPlayer($identifier) {
        // Check cache first
        $cachedData = $this->cache->getPlayerData($identifier);
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        // No valid cache found (either no cache or expired cache was deleted)
        // Fetch fresh data from Hypixel API
        
        // Determine if identifier is UUID or username
        $params = [];
        if (strlen($identifier) > 16) {
            $params['uuid'] = $identifier;
        } else {
            $params['name'] = $identifier;
        }
        
        // Fetch from Hypixel
        $result = $this->fetchFromHypixel('/player', $params);
        
        // Cache and return the data
        return $this->cache->cachePlayerData($result['data'], $result['key_id']);
    }
    
    public function cleanupCache() {
        $cacheResult = $this->cache->cleanup();
        $this->keyManager->cleanupInvalidKeys();
        return $cacheResult;
    }
    
    public function addHypixelKey($apiKey, $owner = null, $notes = null) {
        // Check if API key is empty
        if (empty(trim($apiKey))) {
            throw new Exception('Hypixel API key cannot be empty');
        }
        
        // Validate the key first
        if (!$this->keyManager->checkKeyValidity($apiKey)) {
            throw new Exception('Invalid Hypixel API key');
        }
        
        $sql = "INSERT INTO hypixel_api_keys (api_key, owner, notes) VALUES (?, ?, ?)";
        $this->db->query($sql, [$apiKey, $owner, $notes]);
        return true;
    }
    
    public function getHypixelKeyStats() {
        $sql = "SELECT 
                api_key,
                created_at,
                last_checked,
                last_used,
                daily_requests,
                total_requests,
                is_valid,
                status_code,
                owner,
                notes
                FROM hypixel_api_keys
                ORDER BY is_valid DESC, daily_requests ASC";
        
        $keys = $this->db->query($sql)->fetchAll();
        
        return array_map(function($key) {
            return [
                'key' => $key['api_key'],
                'created_at' => $key['created_at'],
                'last_checked' => $key['last_checked'],
                'last_used' => $key['last_used'],
                'daily_requests' => (int)$key['daily_requests'],
                'total_requests' => (int)$key['total_requests'],
                'is_valid' => (bool)$key['is_valid'],
                'status_code' => (int)$key['status_code'],
                'owner' => $key['owner'],
                'notes' => $key['notes']
            ];
        }, $keys);
    }
} 