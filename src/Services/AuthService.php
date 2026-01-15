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

    /**
     * Get user by ID
     * 
     * @param int $userId User ID
     * @return array|null User data or null if not found
     */
    public function getUserById(int $userId): ?array
    {
        $user = $this->userRepository->find($userId);
        
        if ($user) {
            // Remove password from user data
            unset($user['password']);
        }
        
        return $user;
    }

    /**
     * Update user profile
     * 
     * @param array $data Profile update data
     * @return array ['success' => bool, 'errors' => array, 'user' => array|null]
     */
    public function updateProfile(array $data): array
    {
        $userId = $data['id'];
        $errors = [];
        
        // Get current user
        $currentUser = $this->userRepository->find($userId);
        if (!$currentUser) {
            return [
                'success' => false,
                'errors' => ['general' => ['User not found.']],
                'user' => null
            ];
        }

        // Validate basic fields
        $validator = new Validator($data);
        $validator->validate([
            'email' => 'required|email',
            'username' => 'required|min:3|max:50|alphanumeric',
            'full_name' => 'required|min:2|max:100'
        ]);

        if ($validator->hasErrors()) {
            return [
                'success' => false,
                'errors' => $validator->getErrors(),
                'user' => null
            ];
        }

        // Check if email changed and already exists
        if ($data['email'] !== $currentUser['email']) {
            if ($this->userRepository->emailExists($data['email'])) {
                $errors['email'] = ['This email is already in use.'];
            }
        }

        // Check if username changed and already exists
        if ($data['username'] !== $currentUser['username']) {
            if ($this->userRepository->usernameExists($data['username'])) {
                $errors['username'] = ['This username is already taken.'];
            }
        }

        // Handle password change if provided
        $updateData = [
            'email' => $data['email'],
            'username' => $data['username'],
            'full_name' => $data['full_name']
        ];

        if (!empty($data['new_password'])) {
            // Validate current password
            if (empty($data['current_password'])) {
                $errors['current_password'] = ['Current password is required to change password.'];
            } else {
                if (!$this->passwordHasher->verify($data['current_password'], $currentUser['password'])) {
                    $errors['current_password'] = ['Current password is incorrect.'];
                }
            }

            // Validate new password
            if (strlen($data['new_password']) < 6) {
                $errors['new_password'] = ['New password must be at least 6 characters.'];
            }

            // Validate password confirmation
            if ($data['new_password'] !== $data['confirm_password']) {
                $errors['confirm_password'] = ['Password confirmation does not match.'];
            }

            // If no errors, add password to update data
            if (empty($errors)) {
                $updateData['password'] = $this->passwordHasher->hash($data['new_password']);
            }
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors,
                'user' => null
            ];
        }

        // Update user
        $success = $this->userRepository->update($userId, $updateData);

        if ($success) {
            return [
                'success' => true,
                'errors' => [],
                'user' => [
                    'email' => $updateData['email'],
                    'username' => $updateData['username'],
                    'full_name' => $updateData['full_name']
                ]
            ];
        }

        return [
            'success' => false,
            'errors' => ['general' => ['Profile update failed. Please try again.']],
            'user' => null
        ];
    }
}
