<?php

namespace SellNow\Repositories;

use SellNow\Database\Repository;

/**
 * Product Repository
 * 
 * Handles all database operations related to products.
 * Provides methods for finding products by user, slug, etc.
 * 
 * @package SellNow\Repositories
 */
class ProductRepository extends Repository
{
    /**
     * Table name
     * @var string
     */
    protected string $table = 'products';

    /**
     * Primary key
     * @var string
     */
    protected string $primaryKey = 'product_id';

    /**
     * Find products by user ID
     * 
     * @param int $userId User ID
     * @param bool $activeOnly Only return active products
     * @return array Array of products
     */
    public function findByUserId(int $userId, bool $activeOnly = true): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ?";
        $params = [$userId];

        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }

        $sql .= " ORDER BY product_id DESC";

        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Find a product by slug
     * 
     * @param string $slug Product slug
     * @return array|null Product data or null if not found
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->findOne(['slug' => $slug]);
    }

    /**
     * Get all active products
     * 
     * @param int|null $limit Limit number of results
     * @param int|null $offset Offset for pagination
     * @return array Array of products
     */
    public function getActiveProducts(?int $limit = null, ?int $offset = null): array
    {
        return $this->findAll(['is_active' => 1], $limit, $offset);
    }

    /**
     * Search products by title
     * 
     * @param string $query Search query
     * @param int|null $limit Limit number of results
     * @return array Array of products
     */
    public function search(string $query, ?int $limit = 20): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE is_active = 1 
                AND (title LIKE ? OR description LIKE ?)
                ORDER BY product_id DESC";

        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
        }

        $searchTerm = "%{$query}%";
        $stmt = $this->query($sql, [$searchTerm, $searchTerm]);
        
        return $stmt->fetchAll();
    }

    /**
     * Create a new product
     * 
     * @param array $data Product data
     * @return string|false Product ID or false on failure
     */
    public function create(array $data)
    {
        // Set default values
        $data['is_active'] = $data['is_active'] ?? 1;
        
        return $this->insert($data);
    }

    /**
     * Update product
     * 
     * @param int $productId Product ID
     * @param array $data Data to update
     * @return bool True on success
     */
    public function updateProduct(int $productId, array $data): bool
    {
        return $this->update($productId, $data);
    }

    /**
     * Deactivate a product (soft delete)
     * 
     * @param int $productId Product ID
     * @return bool True on success
     */
    public function deactivate(int $productId): bool
    {
        return $this->update($productId, ['is_active' => 0]);
    }

    /**
     * Activate a product
     * 
     * @param int $productId Product ID
     * @return bool True on success
     */
    public function activate(int $productId): bool
    {
        return $this->update($productId, ['is_active' => 1]);
    }

    /**
     * Check if user owns product
     * 
     * @param int $productId Product ID
     * @param int $userId User ID
     * @return bool True if user owns the product
     */
    public function isOwnedByUser(int $productId, int $userId): bool
    {
        $product = $this->find($productId);
        return $product && (int)$product['user_id'] === $userId;
    }
}
