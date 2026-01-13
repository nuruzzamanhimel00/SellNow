<?php

namespace SellNow\Database;

use PDO;

/**
 * Base Repository Class
 * 
 * Provides common CRUD operations for all repositories.
 * Child repositories can extend this and add specific methods.
 * 
 * @package SellNow\Database
 */
abstract class Repository
{
    /**
     * Database connection
     * @var Connection
     */
    protected Connection $connection;

    /**
     * PDO instance
     * @var PDO
     */
    protected PDO $pdo;

    /**
     * Table name (must be defined in child class)
     * @var string
     */
    protected string $table;

    /**
     * Primary key column name
     * @var string
     */
    protected string $primaryKey = 'id';

    /**
     * Constructor
     * 
     * @param Connection $connection Database connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->pdo = $connection->getPdo();
    }

    /**
     * Find a record by ID
     * 
     * @param int|string $id Record ID
     * @return array|null Record data or null if not found
     */
    public function find($id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Find all records
     * 
     * @param array $conditions WHERE conditions (column => value)
     * @param int|null $limit Limit number of results
     * @param int|null $offset Offset for pagination
     * @return array Array of records
     */
    public function findAll(array $conditions = [], ?int $limit = null, ?int $offset = null): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        // Add WHERE conditions
        if (!empty($conditions)) {
            $whereClauses = [];
            foreach ($conditions as $column => $value) {
                $whereClauses[] = "{$column} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }

        // Add LIMIT and OFFSET
        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
        }
        if ($offset !== null) {
            $sql .= " OFFSET {$offset}";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }

    /**
     * Find one record by conditions
     * 
     * @param array $conditions WHERE conditions (column => value)
     * @return array|null Record data or null if not found
     */
    public function findOne(array $conditions): ?array
    {
        $results = $this->findAll($conditions, 1);
        return $results[0] ?? null;
    }

    /**
     * Insert a new record
     * 
     * @param array $data Data to insert (column => value)
     * @return string|false Last insert ID or false on failure
     */
    public function insert(array $data)
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute(array_values($data));

        return $success ? $this->pdo->lastInsertId() : false;
    }

    /**
     * Update a record by ID
     * 
     * @param int|string $id Record ID
     * @param array $data Data to update (column => value)
     * @return bool True on success
     */
    public function update($id, array $data): bool
    {
        $setClauses = [];
        $params = [];

        foreach ($data as $column => $value) {
            $setClauses[] = "{$column} = ?";
            $params[] = $value;
        }

        $params[] = $id;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClauses) . 
               " WHERE {$this->primaryKey} = ?";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete a record by ID
     * 
     * @param int|string $id Record ID
     * @return bool True on success
     */
    public function delete($id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Count records
     * 
     * @param array $conditions WHERE conditions (column => value)
     * @return int Number of records
     */
    public function count(array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $whereClauses = [];
            foreach ($conditions as $column => $value) {
                $whereClauses[] = "{$column} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        $result = $stmt->fetch();
        return (int) $result['count'];
    }

    /**
     * Check if a record exists
     * 
     * @param int|string $id Record ID
     * @return bool True if exists
     */
    public function exists($id): bool
    {
        return $this->find($id) !== null;
    }

    /**
     * Execute a raw query
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return \PDOStatement
     */
    protected function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
