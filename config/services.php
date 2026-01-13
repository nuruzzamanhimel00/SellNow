<?php

/**
 * Service Container Configuration
 * 
 * Register all services and their dependencies in the DI container.
 * This file is loaded by bootstrap.php.
 * 
 * @var \SellNow\Core\Container $container
 */

use SellNow\Database\Connection;
use SellNow\Repositories\UserRepository;
use SellNow\Repositories\ProductRepository;
use SellNow\Repositories\OrderRepository;
use SellNow\Services\AuthService;
use SellNow\Services\ProductService;
use SellNow\Services\CartService;
use SellNow\Services\PaymentService;
use SellNow\Security\PasswordHasher;
use SellNow\Security\CsrfToken;
use SellNow\Security\InputSanitizer;
use SellNow\Security\FileUploadValidator;
use SellNow\Core\Router;

// Database Connection (Singleton)
$container->singleton(Connection::class, function () {
    return Connection::getInstance();
});

// Security Services (Singletons)
$container->singleton(PasswordHasher::class);
$container->singleton(CsrfToken::class);
$container->singleton(InputSanitizer::class);
$container->singleton(FileUploadValidator::class);

// Repositories (Singletons)
$container->singleton(UserRepository::class, function ($container) {
    return new UserRepository($container->make(Connection::class));
});

$container->singleton(ProductRepository::class, function ($container) {
    return new ProductRepository($container->make(Connection::class));
});

$container->singleton(OrderRepository::class, function ($container) {
    return new OrderRepository($container->make(Connection::class));
});

// Services (Singletons)
$container->singleton(AuthService::class, function ($container) {
    return new AuthService(
        $container->make(UserRepository::class),
        $container->make(PasswordHasher::class)
    );
});

$container->singleton(ProductService::class, function ($container) {
    return new ProductService(
        $container->make(ProductRepository::class),
        $container->make(FileUploadValidator::class)
    );
});

$container->singleton(CartService::class, function ($container) {
    return new CartService(
        $container->make(ProductRepository::class)
    );
});

$container->singleton(PaymentService::class, function ($container) {
    return new PaymentService(
        $container->make(OrderRepository::class)
    );
});

// Router (Singleton)
$container->singleton(Router::class, function ($container) {
    return new Router($container);
});

// Twig Template Engine (Singleton)
$container->singleton(\Twig\Environment::class, function () {
    $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
    $twig = new \Twig\Environment($loader, [
        'debug' => $_ENV['APP_DEBUG'] === 'true',
        'cache' => false // Disable cache for development
    ]);

    // Add global variables
    $twig->addGlobal('session', $_SESSION);
    
    // Add CSRF token functions
    $csrfToken = new CsrfToken();
    
    $twig->addFunction(new \Twig\TwigFunction('csrf_token', function() use ($csrfToken) {
        return $csrfToken->get();
    }));
    
    $twig->addFunction(new \Twig\TwigFunction('csrf_field', function() use ($csrfToken) {
        return $csrfToken->field();
    }));

    return $twig;
});
