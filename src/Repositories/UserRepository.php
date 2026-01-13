<?php

namespace SellNow\Repositories;

use SellNow\Database\Repository;

/**
 * User Repository
 * 
 * Handles all database operations related to users.
 * Provides methods for finding users by email, username, etc.
 * 
 * @package SellNow\Repositories
 */
class UserRepository extends Repository
{
    /**
     * Table name
     * @var string
     */
    protected string $table = 'users';

    /**
     * Primary key
     * @var string
     */
    protected string $primaryKey = 'id';

    /**
     * Find a user by email
     * 
     * @param string $email User email
     * @return array|null User data or null if not found
     */
    public function findByEmail(string $email): ?array
    {
        return $this->findOne(['email' => $email]);
    }

    /**
     * Find a user by username
     * 
     * @param string $username Username
     * @return array|null User data or null if not found
     */
    public function findByUsername(string $username): ?array
    {
        return $this->findOne(['username' => $username]);
    }

    /**
     * Check if email already exists
     * 
     * @param string $email Email to check
     * @param int|null $excludeId User ID to exclude (for updates)
     * @return bool True if email exists
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE email = ?";
        $params = [$email];

        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    }

    /**
     * Check if username already exists
     * 
     * @param string $username Username to check
     * @param int|null $excludeId User ID to exclude (for updates)
     * @return bool True if username exists
     */
    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE username = ?";
        $params = [$username];

        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    }

    /**
     * Create a new user
     * 
     * @param array $data User data (email, username, full_name, password)
     * @return string|false User ID or false on failure
     */
    public function create(array $data)
    {
        // Add timestamp
        $data['created_at'] = date('Y-m-d H:i:s');
        
        return $this->insert($data);
    }

    /**
     * Update user profile
     * 
     * @param int $userId User ID
     * @param array $data Data to update
     * @return bool True on success
     */
    public function updateProfile(int $userId, array $data): bool
    {
        // Remove sensitive fields that shouldn't be updated this way
        unset($data['password'], $data['id'], $data['created_at']);
        
        return $this->update($userId, $data);
    }

    /**
     * Update user password
     * 
     * @param int $userId User ID
     * @param string $hashedPassword Hashed password
     * @return bool True on success
     */
    public function updatePassword(int $userId, string $hashedPassword): bool
    {
        return $this->update($userId, ['password' => $hashedPassword]);
    }
}
