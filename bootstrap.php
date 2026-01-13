<?php

/**
 * Application Bootstrap File
 * 
 * This file initializes the application by:
 * - Loading environment variables
 * - Setting up error handling
 * - Initializing the dependency injection container
 * - Registering core services
 * 
 * @package SellNow
 */

// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use SellNow\Core\Container;
use SellNow\Database\Connection;

// Load environment variables from .env file
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Set error reporting based on environment
if ($_ENV['APP_DEBUG'] === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Set timezone
date_default_timezone_set('Asia/Dhaka');

// Initialize session with secure settings
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_samesite', 'Lax');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize dependency injection container
$container = Container::getInstance();

// Register core services
require_once __DIR__ . '/config/services.php';

// Return container for use in application
return $container;
