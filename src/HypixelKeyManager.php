<?php
class HypixelKeyManager {
    private $db;
    private $config;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->config = require __DIR__ . '/../config.php';
    }
    
    public function addKey($apiKey) {
        // Check if API key is empty
        if (empty(trim($apiKey))) {
            throw new Exception('Hypixel API key cannot be empty');
        }
        
        // Validate the key first
        if (!$this->checkKeyValidity($apiKey)) {
            throw new Exception('Invalid Hypixel API key');
        }
        
        $sql = "INSERT INTO hypixel_api_keys (api_key) VALUES (?)";
        $this->db->query($sql, [$apiKey]);
        return true;
    }
    
    public function checkKeyValidity($apiKey) {
        $url = $this->config['hypixel']['base_url'] . '/key';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'API-Key: ' . $apiKey
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Parse the response
        $data = json_decode($response, true);
        
        // Key is only invalid if Hypixel API explicitly says so
        if (isset($data['success']) && $data['success'] === false && 
            isset($data['cause']) && $data['cause'] === 'Invalid API key') {
            return false;
        }
        
        return true;
    }
    
    public function getValidKey() {
        // Get a valid key with the least daily requests
        $sql = "SELECT id, api_key, daily_requests 
                FROM hypixel_api_keys 
                WHERE is_valid = true 
                AND DATE(last_used) = CURDATE()
                ORDER BY daily_requests ASC 
                LIMIT 1";
        
        $result = $this->db->query($sql)->fetch();
        
        if (!$result) {
            // Try keys that haven't been used today
            $sql = "SELECT id, api_key 
                    FROM hypixel_api_keys 
                    WHERE is_valid = true 
                    AND DATE(last_used) < CURDATE()
                    LIMIT 1";
            $result = $this->db->query($sql)->fetch();
            
            if ($result) {
                // Reset daily requests for new day
                $sql = "UPDATE hypixel_api_keys SET daily_requests = 0 WHERE id = ?";
                $this->db->query($sql, [$result['id']]);
            }
        }
        
        if (!$result) {
            throw new Exception('No valid Hypixel API keys available');
        }
        
        return $result;
    }
    
    public function updateKeyStatus($keyId, $statusCode) {
        $isValid = $statusCode === 200;
        
        if ($isValid) {
            // If key is valid, just update the status
            $sql = "UPDATE hypixel_api_keys 
                    SET is_valid = 1, 
                        status_code = ?, 
                        last_checked = NOW(),
                        daily_requests = daily_requests + 1 
                    WHERE id = ?";
            
            $this->db->query($sql, [$statusCode, $keyId]);
        } else {
            // If key is invalid, delete it
            $sql = "DELETE FROM hypixel_api_keys WHERE id = ?";
            $this->db->query($sql, [$keyId]);
            
            // Also remove references to this key from cache
            $sql = "UPDATE player_cache SET hypixel_key_id = NULL WHERE hypixel_key_id = ?";
            $this->db->query($sql, [$keyId]);
        }
    }
    
    public function getKeyStatistics() {
        $sql = "SELECT 
                    api_key,
                    is_valid,
                    status_code,
                    daily_requests,
                    last_checked,
                    last_used
                FROM hypixel_api_keys
                ORDER BY is_valid DESC, daily_requests ASC";
        
        return $this->db->query($sql)->fetchAll();
    }
    
    public function cleanupInvalidKeys() {
        // Remove keys that have been invalid for more than a day
        $sql = "DELETE FROM hypixel_api_keys 
                WHERE is_valid = false 
                AND last_checked < DATE_SUB(NOW(), INTERVAL 1 DAY)";
        
        $this->db->query($sql);
    }
} 