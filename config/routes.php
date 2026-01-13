<?php

/**
 * Route Definitions
 * 
 * Define all application routes with their controllers and middleware.
 * This file is loaded by public/index.php.
 * 
 * @var \SellNow\Core\Router $router
 */

use SellNow\Controllers\AuthController;
use SellNow\Controllers\ProductController;
use SellNow\Controllers\CartController;
use SellNow\Controllers\CheckoutController;
use SellNow\Controllers\PublicController;
use SellNow\Middleware\AuthMiddleware;
use SellNow\Middleware\CsrfMiddleware;

// Public Routes (Specific)
$router->get('/', [PublicController::class, 'home']);

// Authentication Routes
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login'], [CsrfMiddleware::class]);
$router->get('/register', [AuthController::class, 'registerForm']);
$router->post('/register', [AuthController::class, 'register'], [CsrfMiddleware::class]);
$router->get('/logout', [AuthController::class, 'logout']);

// Protected Routes (Require Authentication)
$router->get('/dashboard', [AuthController::class, 'dashboard'], [AuthMiddleware::class]);

// Product Routes
$router->get('/products/add', [ProductController::class, 'create'], [AuthMiddleware::class]);
$router->post('/products/add', [ProductController::class, 'store'], [AuthMiddleware::class, CsrfMiddleware::class]);

// Cart Routes
$router->get('/cart', [CartController::class, 'index']);
$router->post('/cart/add', [CartController::class, 'add'], [CsrfMiddleware::class]);
$router->post('/cart/remove', [CartController::class, 'remove'], [CsrfMiddleware::class]);
$router->get('/cart/clear', [CartController::class, 'clear']);

// Checkout Routes
$router->get('/checkout', [CheckoutController::class, 'index'], [AuthMiddleware::class]);
$router->post('/checkout/process', [CheckoutController::class, 'process'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/checkout/success', [CheckoutController::class, 'success'], [AuthMiddleware::class]);

// Public Routes (Wildcard should be last)
$router->get('/{username}', [PublicController::class, 'profile']);
