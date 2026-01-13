<?php

namespace SellNow\Database;

use PDO;
use PDOException;
use Exception;

/**
 * Database Connection Manager
 * 
 * Manages database connections with environment-based configuration.
 * Supports both SQLite and MySQL with proper error handling.
 * 
 * @package SellNow\Database
 */
class Connection
{
    /**
     * Singleton instance
     * @var Connection|null
     */
    private static ?Connection $instance = null;

    /**
     * PDO connection
     * @var PDO|null
     */
    private ?PDO $pdo = null;

    /**
     * Private constructor for singleton pattern
     * 
     * @throws Exception If connection fails
     */
    private function __construct()
    {
        $this->connect();
    }

    /**
     * Get singleton instance
     * 
     * @return Connection
     */
    public static function getInstance(): Connection
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Establish database connection
     * 
     * @return void
     * @throws Exception If connection fails
     */
    private function connect(): void
    {
        try {
            $driver = $_ENV['DB_CONNECTION'] ?? 'sqlite';

            if ($driver === 'sqlite') {
                $this->connectSqlite();
            } elseif ($driver === 'mysql') {
                $this->connectMysql();
            } else {
                throw new Exception("Unsupported database driver: {$driver}");
            }

            // Set PDO attributes for better error handling
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        } catch (PDOException $e) {
            // Log error in production, show in development
            if ($_ENV['APP_DEBUG'] === 'true') {
                throw new Exception("Database connection failed: " . $e->getMessage());
            } else {
                // Log to file
                error_log("Database connection failed: " . $e->getMessage());
                throw new Exception("Database connection failed. Please try again later.");
            }
        }
    }

    /**
     * Connect to SQLite database
     * 
     * @return void
     * @throws PDOException If connection fails
     */
    private function connectSqlite(): void
    {
        $dbPath = $_ENV['DB_PATH'] ?? 'database/database.sqlite';
        
        // Convert relative path to absolute
        if (!str_starts_with($dbPath, '/') && !preg_match('/^[A-Z]:/i', $dbPath)) {
            $dbPath = dirname(__DIR__, 2) . '/' . $dbPath;
        }

        $this->pdo = new PDO("sqlite:{$dbPath}");
    }

    /**
     * Connect to MySQL database
     * 
     * @return void
     * @throws PDOException If connection fails
     */
    private function connectMysql(): void
    {
        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $database = $_ENV['DB_DATABASE'] ?? 'sellnow';
        $username = $_ENV['DB_USERNAME'] ?? 'root';
        $password = $_ENV['DB_PASSWORD'] ?? '';

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
        $this->pdo = new PDO($dsn, $username, $password);
    }

    /**
     * Get PDO connection
     * 
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Begin a transaction
     * 
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit a transaction
     * 
     * @return bool
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback a transaction
     * 
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Get the last inserted ID
     * 
     * @return string
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Execute a raw query (use with caution)
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return \PDOStatement
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
