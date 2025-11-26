-- Create database if not exists (uncomment and replace with your database name)
-- CREATE DATABASE IF NOT EXISTS hypixel_mirror CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE hypixel_mirror;

-- 使用现有的api数据库
-- CREATE DATABASE IF NOT EXISTS api CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE api;

-- Hypixel API keys table
CREATE TABLE IF NOT EXISTS hypixel_api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key VARCHAR(36) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_checked TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    daily_requests INT DEFAULT 0,
    total_requests BIGINT DEFAULT 0,
    is_valid BOOLEAN DEFAULT true,
    status_code INT DEFAULT 200,
    owner VARCHAR(64) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    INDEX idx_valid_key (is_valid),
    INDEX idx_last_used (last_used)
);

-- Player data cache table (修复唯一约束问题)
CREATE TABLE IF NOT EXISTS player_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player_uuid VARCHAR(36),
    player_name VARCHAR(32),
    player_data MEDIUMTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    hypixel_key_id INT,
    hit_count INT DEFAULT 0,
    last_hit TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_uuid (player_uuid),
    INDEX idx_name (player_name),
    INDEX idx_expires (expires_at),
    INDEX idx_hit_count (hit_count),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (hypixel_key_id) REFERENCES hypixel_api_keys(id) ON DELETE SET NULL
);

-- API request logs table
CREATE TABLE IF NOT EXISTS request_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hypixel_key_id INT,
    request_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    endpoint VARCHAR(255) NOT NULL,
    response_code INT NOT NULL,
    request_type ENUM('API', 'CACHE') DEFAULT 'API',
    request_date DATE GENERATED ALWAYS AS (DATE(request_time)) STORED,
    INDEX idx_time (request_time),
    INDEX idx_key (hypixel_key_id),
    INDEX idx_date (request_date),
    INDEX idx_type_date (request_type, request_date),
    FOREIGN KEY (hypixel_key_id) REFERENCES hypixel_api_keys(id) ON DELETE SET NULL
);

-- Migration script to fix existing duplicate cache entries
-- Run this after updating the table structure:
/*
-- Step 1: Remove duplicate entries, keeping only the most recent one for each player
DELETE pc1 FROM player_cache pc1
INNER JOIN player_cache pc2 
WHERE pc1.id < pc2.id 
AND (
    (pc1.player_uuid IS NOT NULL AND pc1.player_uuid = pc2.player_uuid) OR
    (pc1.player_name IS NOT NULL AND pc1.player_name = pc2.player_name)
);

-- Step 2: Add the unique constraints (if not already added above)
ALTER TABLE player_cache ADD UNIQUE KEY unique_uuid_cache (player_uuid);
ALTER TABLE player_cache ADD UNIQUE KEY unique_name_cache (player_name);
*/

