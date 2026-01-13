<?php

namespace SellNow\Services;

use SellNow\Repositories\ProductRepository;

/**
 * Cart Service
 * 
 * Manages shopping cart operations (session-based).
 * Handles adding/removing items and calculating totals.
 * 
 * @package SellNow\Services
 */
class CartService
{
    /**
     * Product repository
     * @var ProductRepository
     */
    private ProductRepository $productRepository;

    /**
     * Session key for cart
     * @var string
     */
    private const CART_KEY = 'cart';

    /**
     * Constructor
     * 
     * @param ProductRepository $productRepository Product repository
     */
    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * Add item to cart
     * 
     * @param int $productId Product ID
     * @param int $quantity Quantity
     * @return array ['success' => bool, 'message' => string]
     */
    public function addItem(int $productId, int $quantity = 1): array
    {
        // Validate product exists
        $product = $this->productRepository->find($productId);
        
        if (!$product) {
            return [
                'success' => false,
                'message' => 'Product not found.'
            ];
        }

        // Initialize cart if not exists
        if (!isset($_SESSION[self::CART_KEY])) {
            $_SESSION[self::CART_KEY] = [];
        }

        // Check if product already in cart
        $existingIndex = $this->findCartItemIndex($productId);

        if ($existingIndex !== null) {
            // Update quantity
            $_SESSION[self::CART_KEY][$existingIndex]['quantity'] += $quantity;
        } else {
            // Add new item
            $_SESSION[self::CART_KEY][] = [
                'product_id' => $product['product_id'],
                'title' => $product['title'],
                'price' => $product['price'],
                'quantity' => $quantity,
                'image_path' => $product['image_path']
            ];
        }

        return [
            'success' => true,
            'message' => 'Product added to cart.'
        ];
    }

    /**
     * Remove item from cart
     * 
     * @param int $productId Product ID
     * @return bool True on success
     */
    public function removeItem(int $productId): bool
    {
        $index = $this->findCartItemIndex($productId);

        if ($index !== null) {
            array_splice($_SESSION[self::CART_KEY], $index, 1);
            return true;
        }

        return false;
    }

    /**
     * Update item quantity
     * 
     * @param int $productId Product ID
     * @param int $quantity New quantity
     * @return bool True on success
     */
    public function updateQuantity(int $productId, int $quantity): bool
    {
        $index = $this->findCartItemIndex($productId);

        if ($index !== null) {
            if ($quantity <= 0) {
                return $this->removeItem($productId);
            }

            $_SESSION[self::CART_KEY][$index]['quantity'] = $quantity;
            return true;
        }

        return false;
    }

    /**
     * Get all cart items
     * 
     * @return array Cart items
     */
    public function getItems(): array
    {
        return $_SESSION[self::CART_KEY] ?? [];
    }

    /**
     * Get cart item count
     * 
     * @return int Number of items in cart
     */
    public function getItemCount(): int
    {
        return count($this->getItems());
    }

    /**
     * Calculate cart total
     * 
     * @return float Total amount
     */
    public function getTotal(): float
    {
        $total = 0;

        foreach ($this->getItems() as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        return $total;
    }

    /**
     * Clear cart
     * 
     * @return void
     */
    public function clear(): void
    {
        unset($_SESSION[self::CART_KEY]);
    }

    /**
     * Check if cart is empty
     * 
     * @return bool True if empty
     */
    public function isEmpty(): bool
    {
        return empty($_SESSION[self::CART_KEY]);
    }

    /**
     * Find cart item index by product ID
     * 
     * @param int $productId Product ID
     * @return int|null Index or null if not found
     */
    private function findCartItemIndex(int $productId): ?int
    {
        if (!isset($_SESSION[self::CART_KEY])) {
            return null;
        }

        foreach ($_SESSION[self::CART_KEY] as $index => $item) {
            if ((int)$item['product_id'] === $productId) {
                return $index;
            }
        }

        return null;
    }
}
