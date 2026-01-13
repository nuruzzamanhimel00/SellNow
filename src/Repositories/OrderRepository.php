<?php

namespace SellNow\Repositories;

use SellNow\Database\Repository;

/**
 * Order Repository
 * 
 * Handles all database operations related to orders.
 * 
 * @package SellNow\Repositories
 */
class OrderRepository extends Repository
{
    /**
     * Table name
     * @var string
     */
    protected string $table = 'orders';

    /**
     * Primary key
     * @var string
     */
    protected string $primaryKey = 'id';

    /**
     * Find orders by user ID
     * 
     * @param int $userId User ID
     * @param int|null $limit Limit number of results
     * @return array Array of orders
     */
    public function findByUserId(int $userId, ?int $limit = null): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY order_date DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
        }

        $stmt = $this->query($sql, [$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Find order by transaction ID
     * 
     * @param string $transactionId Transaction ID
     * @return array|null Order data or null if not found
     */
    public function findByTransactionId(string $transactionId): ?array
    {
        return $this->findOne(['transaction_id' => $transactionId]);
    }

    /**
     * Create a new order
     * 
     * @param array $data Order data
     * @return string|false Order ID or false on failure
     */
    public function create(array $data)
    {
        // Add timestamp
        $data['order_date'] = $data['order_date'] ?? date('Y-m-d H:i:s');
        
        return $this->insert($data);
    }

    /**
     * Update order payment status
     * 
     * @param int $orderId Order ID
     * @param string $status Payment status
     * @param string|null $transactionId Transaction ID
     * @return bool True on success
     */
    public function updatePaymentStatus(int $orderId, string $status, ?string $transactionId = null): bool
    {
        $data = ['payment_status' => $status];
        
        if ($transactionId !== null) {
            $data['transaction_id'] = $transactionId;
        }

        return $this->update($orderId, $data);
    }

    /**
     * Get order statistics for a user
     * 
     * @param int $userId User ID
     * @return array Statistics (total_orders, total_spent)
     */
    public function getUserStats(int $userId): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    COALESCE(SUM(total_amount), 0) as total_spent
                FROM {$this->table}
                WHERE user_id = ? AND payment_status = 'completed'";

        $stmt = $this->query($sql, [$userId]);
        return $stmt->fetch();
    }
}
