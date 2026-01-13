<?php

namespace SellNow\Security;

/**
 * Password Hashing Service
 * 
 * Provides secure password hashing using bcrypt algorithm.
 * Uses PHP's password_hash() and password_verify() functions.
 * 
 * @package SellNow\Security
 */
class PasswordHasher
{
    /**
     * Hash a password
     * 
     * @param string $password Plain text password
     * @return string Hashed password
     */
    public function hash(string $password): string
    {
        // Use bcrypt algorithm with default cost (currently 10)
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Verify a password against a hash
     * 
     * @param string $password Plain text password
     * @param string $hash Hashed password
     * @return bool True if password matches hash
     */
    public function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Check if a hash needs to be rehashed
     * 
     * Useful when password hashing algorithm or cost changes.
     * 
     * @param string $hash Hashed password
     * @return bool True if needs rehashing
     */
    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT);
    }
}
