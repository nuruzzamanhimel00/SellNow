<?php

namespace SellNow\Security;

/**
 * File Upload Validator
 * 
 * Validates uploaded files for security:
 * - MIME type validation
 * - File size limits
 * - Extension whitelist
 * - Path traversal prevention
 * 
 * @package SellNow\Security
 */
class FileUploadValidator
{
    /**
     * Maximum file size in bytes
     * @var int
     */
    private int $maxSize;

    /**
     * Allowed MIME types for images
     * @var array
     */
    private array $allowedImageMimes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp'
    ];

    /**
     * Allowed extensions for images
     * @var array
     */
    private array $allowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * Allowed extensions for files
     * @var array
     */
    private array $allowedFileExtensions = ['pdf', 'zip', 'rar', 'doc', 'docx'];

    /**
     * Constructor
     * 
     * @param int|null $maxSize Maximum file size (uses env var if not provided)
     */
    public function __construct(?int $maxSize = null)
    {
        $this->maxSize = $maxSize ?? (int) ($_ENV['MAX_UPLOAD_SIZE'] ?? 10485760); // 10MB default
    }

    /**
     * Validate an uploaded image
     * 
     * @param array $file Uploaded file from $_FILES
     * @return array ['valid' => bool, 'error' => string|null]
     */
    public function validateImage(array $file): array
    {
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'File upload failed'];
        }

        // Check file size
        if ($file['size'] > $this->maxSize) {
            $maxMB = round($this->maxSize / 1048576, 2);
            return ['valid' => false, 'error' => "File size exceeds {$maxMB}MB limit"];
        }

        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->allowedImageMimes)) {
            return ['valid' => false, 'error' => 'Invalid image type. Allowed: JPG, PNG, GIF, WebP'];
        }

        // Check extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedImageExtensions)) {
            return ['valid' => false, 'error' => 'Invalid image extension'];
        }

        // Check for path traversal attempts
        if ($this->hasPathTraversal($file['name'])) {
            return ['valid' => false, 'error' => 'Invalid filename'];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Validate an uploaded file (non-image)
     * 
     * @param array $file Uploaded file from $_FILES
     * @return array ['valid' => bool, 'error' => string|null]
     */
    public function validateFile(array $file): array
    {
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'File upload failed'];
        }

        // Check file size
        if ($file['size'] > $this->maxSize) {
            $maxMB = round($this->maxSize / 1048576, 2);
            return ['valid' => false, 'error' => "File size exceeds {$maxMB}MB limit"];
        }

        // Check extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedFileExtensions)) {
            $allowed = implode(', ', $this->allowedFileExtensions);
            return ['valid' => false, 'error' => "Invalid file type. Allowed: {$allowed}"];
        }

        // Check for path traversal attempts
        if ($this->hasPathTraversal($file['name'])) {
            return ['valid' => false, 'error' => 'Invalid filename'];
        }

        // Check for executable files (additional security)
        $dangerousExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'exe', 'sh', 'bat', 'cmd'];
        if (in_array($extension, $dangerousExtensions)) {
            return ['valid' => false, 'error' => 'Executable files are not allowed'];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Check for path traversal attempts in filename
     * 
     * @param string $filename Filename to check
     * @return bool True if path traversal detected
     */
    private function hasPathTraversal(string $filename): bool
    {
        // Check for directory traversal patterns
        if (strpos($filename, '..') !== false) {
            return true;
        }

        if (strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Generate a safe filename
     * 
     * @param string $originalName Original filename
     * @return string Safe filename with timestamp
     */
    public function generateSafeFilename(string $originalName): string
    {
        // Get extension
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        // Generate unique filename
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        
        return "{$timestamp}_{$random}.{$extension}";
    }
}
