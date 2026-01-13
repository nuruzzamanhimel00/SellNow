<?php

namespace SellNow\Config;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $conn;

    private $host = '127.0.0.1'; // TODO: Move to env
    private $db_name = 'sellnow'; 
    private $username = 'root';
    private $password = ''; // user might need to change this

    private function __construct() {
        // Checking for SQLite first for this assessment
        $isSqlite = true; // Hardcoded flip for now

        try {
            if ($isSqlite) {
                // Determine absolute path to database.sqlite
                $dbPath = __DIR__ . '/../../database/database.sqlite';
                $this->conn = new PDO("sqlite:" . $dbPath);
            } else {
                // Fallback to MySQL
                $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            }
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
            exit; // Hard exit!
        }
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }
    
    // Helper to just run a query
    public function query($sql) {
        return $this->conn->query($sql); // No preparation? Risk!
    }
}
