<?php

namespace SellNow\Security;

/**
 * CSRF Token Manager
 * 
 * Generates and validates CSRF tokens to prevent Cross-Site Request Forgery attacks.
 * Tokens are stored in session and must be included in state-changing requests.
 * 
 * @package SellNow\Security
 */
class CsrfToken
{
    /**
     * Session key for CSRF token
     * @var string
     */
    private const TOKEN_KEY = '_csrf_token';

    /**
     * Generate a new CSRF token
     * 
     * @return string The generated token
     */
    public function generate(): string
    {
        // Generate a random token
        $token = bin2hex(random_bytes(32));

        // Store in session
        $_SESSION[self::TOKEN_KEY] = $token;

        return $token;
    }

    /**
     * Get the current CSRF token (generates if not exists)
     * 
     * @return string The current token
     */
    public function get(): string
    {
        if (!isset($_SESSION[self::TOKEN_KEY])) {
            return $this->generate();
        }

        return $_SESSION[self::TOKEN_KEY];
    }

    /**
     * Validate a CSRF token
     * 
     * @param string|null $token Token to validate
     * @return bool True if valid
     */
    public function validate(?string $token): bool
    {
        if ($token === null) {
            return false;
        }

        if (!isset($_SESSION[self::TOKEN_KEY])) {
            return false;
        }

        // Use hash_equals to prevent timing attacks
        return hash_equals($_SESSION[self::TOKEN_KEY], $token);
    }

    /**
     * Regenerate the CSRF token
     * 
     * Useful after login or other sensitive operations.
     * 
     * @return string The new token
     */
    public function regenerate(): string
    {
        return $this->generate();
    }

    /**
     * Get HTML input field for CSRF token
     * 
     * @return string HTML input field
     */
    public function field(): string
    {
        $token = $this->get();
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token) . '">';
    }
}
