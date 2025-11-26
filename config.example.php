<?php
return [
    // 数据库配置
    'database' => [
        'host' => 'localhost',          // 数据库主机地址
        'dbname' => 'api',              // 使用现有的api数据库
        'username' => 'your_username',  // 数据库用户名
        'password' => 'your_password',  // 数据库密码
        'charset' => 'utf8mb4'
    ],
    
    // Hypixel API 配置
    'hypixel' => [
        'base_url' => 'https://api.hypixel.net',
        'cache_duration' => 10800, // 3 hours in seconds (changed from 3 days)
    ],
    
    // 管理员配置
    'admin' => [
        'username' => 'admin',          // 管理员用户名
        'password' => 'change_this'     // 管理员密码，请修改为安全的密码
    ]
]; 