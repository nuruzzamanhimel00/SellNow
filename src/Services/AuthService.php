<?php

namespace SellNow\Services;

use SellNow\Repositories\UserRepository;
use SellNow\Security\PasswordHasher;
use SellNow\Validation\Validator;

/**
 * Authentication Service
 * 
 * Handles user registration, login, and session management.
 * Implements business logic for authentication.
 * 
 * @package SellNow\Services
 */
class AuthService
{
    /**
     * User repository
     * @var UserRepository
     */
    private UserRepository $userRepository;

    /**
     * Password hasher
     * @var PasswordHasher
     */
    private PasswordHasher $passwordHasher;

    /**
     * Constructor
     * 
     * @param UserRepository $userRepository User repository
     * @param PasswordHasher $passwordHasher Password hasher
     */
    public function __construct(UserRepository $userRepository, PasswordHasher $passwordHasher)
    {
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * Register a new user
     * 
     * @param array $data User registration data
     * @return array ['success' => bool, 'errors' => array, 'user_id' => int|null]
     */
    public function register(array $data): array
    {
        // Validate input
        $validator = new Validator($data);
        $validator->validate([
            'email' => 'required|email',
            'username' => 'required|min:3|max:50|alphanumeric',
            'full_name' => 'required|min:2|max:100',
            'password' => 'required|min:6'
        ]);

        if ($validator->hasErrors()) {
            return [
                'success' => false,
                'errors' => $validator->getErrors(),
                'user_id' => null
            ];
        }

        // Check if email already exists
        if ($this->userRepository->emailExists($data['email'])) {
            return [
                'success' => false,
                'errors' => ['email' => ['This email is already registered.']],
                'user_id' => null
            ];
        }

        // Check if username already exists
        if ($this->userRepository->usernameExists($data['username'])) {
            return [
                'success' => false,
                'errors' => ['username' => ['This username is already taken.']],
                'user_id' => null
            ];
        }

        // Hash password
        $hashedPassword = $this->passwordHasher->hash($data['password']);

        // Create user
        $userId = $this->userRepository->create([
            'email' => $data['email'],
            'username' => $data['username'],
            'full_name' => $data['full_name'],
            'password' => $hashedPassword
        ]);

        if ($userId) {
            return [
                'success' => true,
                'errors' => [],
                'user_id' => (int)$userId
            ];
        }

        return [
            'success' => false,
            'errors' => ['general' => ['Registration failed. Please try again.']],
            'user_id' => null
        ];
    }

    /**
     * Authenticate user login
     * 
     * @param string $email User email
     * @param string $password User password
     * @return array ['success' => bool, 'user' => array|null, 'error' => string|null]
     */
    public function login(string $email, string $password): array
    {
        // Find user by email
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            return [
                'success' => false,
                'user' => null,
                'error' => 'Invalid email or password.'
            ];
        }

        // Verify password
        if (!$this->passwordHasher->verify($password, $user['password'])) {
            return [
                'success' => false,
                'user' => null,
                'error' => 'Invalid email or password.'
            ];
        }

        // Remove password from user data before returning
        unset($user['password']);

        return [
            'success' => true,
            'user' => $user,
            'error' => null
        ];
    }

    /**
     * Create user session
     * 
     * @param array $user User data
     * @return void
     */
    public function createSession(array $user): void
    {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        // Store user data in session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['logged_in_at'] = time();
    }

    /**
     * Destroy user session (logout)
     * 
     * @return void
     */
    public function logout(): void
    {
        // Clear session data
        $_SESSION = [];

        // Destroy session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        // Destroy session
        session_destroy();
    }

    /**
     * Check if user is authenticated
     * 
     * @return bool True if authenticated
     */
    public function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Get current authenticated user
     * 
     * @return array|null User data or null if not authenticated
     */
    public function getCurrentUser(): ?array
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return $this->userRepository->find($_SESSION['user_id']);
    }

    /**
     * Get current user ID
     * 
     * @return int|null User ID or null if not authenticated
     */
    public function getCurrentUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }
}
