<?php
class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        $config = require __DIR__ . '/../config.php';
        $dsn = "mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset=utf8mb4";
        
        try {
            $this->pdo = new PDO(
                $dsn,
                $config['database']['username'],
                $config['database']['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            throw new Exception("Connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
} 