<?php

namespace SellNow\Services;

use SellNow\Repositories\ProductRepository;
use SellNow\Security\FileUploadValidator;
use SellNow\Validation\Validator;

/**
 * Product Service
 * 
 * Handles product creation, updates, and file uploads.
 * Implements business logic for product management.
 * 
 * @package SellNow\Services
 */
class ProductService
{
    /**
     * Product repository
     * @var ProductRepository
     */
    private ProductRepository $productRepository;

    /**
     * File upload validator
     * @var FileUploadValidator
     */
    private FileUploadValidator $fileValidator;

    /**
     * Upload directory
     * @var string
     */
    private string $uploadDir;

    /**
     * Constructor
     * 
     * @param ProductRepository $productRepository Product repository
     * @param FileUploadValidator $fileValidator File upload validator
     */
    public function __construct(ProductRepository $productRepository, FileUploadValidator $fileValidator)
    {
        $this->productRepository = $productRepository;
        $this->fileValidator = $fileValidator;
        $this->uploadDir = dirname(__DIR__, 2) . '/public/uploads/';

        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Create a new product
     * 
     * @param array $data Product data
     * @param array $files Uploaded files
     * @param int $userId User ID
     * @return array ['success' => bool, 'errors' => array, 'product_id' => int|null]
     */
    public function createProduct(array $data, array $files, int $userId): array
    {
        // Validate input
        $validator = new Validator($data);
        $validator->validate([
            'title' => 'required|min:3|max:255',
            'description' => 'max:5000',
            'price' => 'required|numeric'
        ]);

        if ($validator->hasErrors()) {
            return [
                'success' => false,
                'errors' => $validator->getErrors(),
                'product_id' => null
            ];
        }

        $errors = [];

        // Handle image upload
        $imagePath = null;
        if (isset($files['image']) && $files['image']['error'] === UPLOAD_ERR_OK) {
            $imageValidation = $this->fileValidator->validateImage($files['image']);
            
            if (!$imageValidation['valid']) {
                $errors['image'] = [$imageValidation['error']];
            } else {
                $imagePath = $this->uploadFile($files['image']);
            }
        }

        // Handle product file upload
        $filePath = null;
        if (isset($files['product_file']) && $files['product_file']['error'] === UPLOAD_ERR_OK) {
            $fileValidation = $this->fileValidator->validateFile($files['product_file']);
            
            if (!$fileValidation['valid']) {
                $errors['product_file'] = [$fileValidation['error']];
            } else {
                $filePath = $this->uploadFile($files['product_file']);
            }
        }

        // Return errors if any
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors,
                'product_id' => null
            ];
        }

        // Generate slug
        $slug = $this->generateSlug($data['title']);

        // Create product
        $productId = $this->productRepository->create([
            'user_id' => $userId,
            'title' => $data['title'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'image_path' => $imagePath,
            'file_path' => $filePath
        ]);

        if ($productId) {
            return [
                'success' => true,
                'errors' => [],
                'product_id' => (int)$productId
            ];
        }

        return [
            'success' => false,
            'errors' => ['general' => ['Failed to create product. Please try again.']],
            'product_id' => null
        ];
    }

    /**
     * Upload a file
     * 
     * @param array $file File from $_FILES
     * @return string Relative path to uploaded file
     */
    private function uploadFile(array $file): string
    {
        $safeFilename = $this->fileValidator->generateSafeFilename($file['name']);
        $destination = $this->uploadDir . $safeFilename;

        move_uploaded_file($file['tmp_name'], $destination);

        return 'uploads/' . $safeFilename;
    }

    /**
     * Generate a unique slug from title
     * 
     * @param string $title Product title
     * @return string Unique slug
     */
    private function generateSlug(string $title): string
    {
        // Convert to lowercase and replace spaces with hyphens
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        // Add random suffix for uniqueness
        $slug .= '-' . substr(md5(uniqid()), 0, 8);

        return $slug;
    }

    /**
     * Get products by user
     * 
     * @param int $userId User ID
     * @return array Array of products
     */
    public function getUserProducts(int $userId): array
    {
        return $this->productRepository->findByUserId($userId);
    }

    /**
     * Get a product by ID
     * 
     * @param int $productId Product ID
     * @return array|null Product data or null
     */
    public function getProduct(int $productId): ?array
    {
        return $this->productRepository->find($productId);
    }

    /**
     * Get a product by slug
     * 
     * @param string $slug Product slug
     * @return array|null Product data or null
     */
    public function getProductBySlug(string $slug): ?array
    {
        return $this->productRepository->findBySlug($slug);
    }

    /**
     * Search products
     * 
     * @param string $query Search query
     * @return array Array of products
     */
    public function searchProducts(string $query): array
    {
        return $this->productRepository->search($query);
    }

    /**
     * Check if user owns product
     * 
     * @param int $productId Product ID
     * @param int $userId User ID
     * @return bool True if user owns the product
     */
    public function userOwnsProduct(int $productId, int $userId): bool
    {
        return $this->productRepository->isOwnedByUser($productId, $userId);
    }
}
