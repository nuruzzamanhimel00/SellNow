<?php

namespace SellNow\Security;

/**
 * Input Sanitization Service
 * 
 * Provides methods to sanitize user input and prevent XSS attacks.
 * 
 * @package SellNow\Security
 */
class InputSanitizer
{
    /**
     * Sanitize a string for safe output
     * 
     * @param string $input Raw input
     * @return string Sanitized output
     */
    public function sanitize(string $input): string
    {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Sanitize an email address
     * 
     * @param string $email Raw email
     * @return string|false Sanitized email or false if invalid
     */
    public function sanitizeEmail(string $email)
    {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }

    /**
     * Sanitize a URL
     * 
     * @param string $url Raw URL
     * @return string|false Sanitized URL or false if invalid
     */
    public function sanitizeUrl(string $url)
    {
        return filter_var($url, FILTER_SANITIZE_URL);
    }

    /**
     * Sanitize an integer
     * 
     * @param mixed $value Raw value
     * @return int Sanitized integer
     */
    public function sanitizeInt($value): int
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Sanitize a float
     * 
     * @param mixed $value Raw value
     * @return float Sanitized float
     */
    public function sanitizeFloat($value): float
    {
        return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    /**
     * Strip all HTML tags from input
     * 
     * @param string $input Raw input
     * @param string|null $allowedTags Allowed tags (e.g., '<p><a>')
     * @return string Sanitized output
     */
    public function stripTags(string $input, ?string $allowedTags = null): string
    {
        return strip_tags($input, $allowedTags);
    }

    /**
     * Sanitize an array of values
     * 
     * @param array $data Raw data
     * @return array Sanitized data
     */
    public function sanitizeArray(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $sanitized[$key] = $this->sanitize($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
