<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SellNow\Config\Database;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

session_start();

// Basic Twig Setup (Global-ish)
$loader = new FilesystemLoader(__DIR__ . '/../templates');
$twig = new Environment($loader, ['debug' => true]);
$twig->addGlobal('session', $_SESSION);

// Database Connection
$db = Database::getInstance()->getConnection();

// Basic Routing (Switch Statement)
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Simple helper for redirection
function redirect($url)
{
    header("Location: $url");
    exit;
}

// Router
switch ($uri) {
    case '/':
        echo $twig->render('layouts/base.html.twig', ['content' => "<h1>Welcome</h1><a href='/login'>Login</a>"]);
        break;

    case '/login':
        require_once __DIR__ . '/../src/Controllers/AuthController.php';
        $auth = new \SellNow\Controllers\AuthController($twig, $db);
        if ($method === 'POST')
            $auth->login();
        else
            $auth->loginForm();
        break;

    case '/register':
        require_once __DIR__ . '/../src/Controllers/AuthController.php';
        $auth = new \SellNow\Controllers\AuthController($twig, $db);
        if ($method === 'POST')
            $auth->register();
        else
            $auth->registerForm();
        break;

    case '/logout':
        session_destroy();
        redirect('/');
        break;

    case '/dashboard':
        require_once __DIR__ . '/../src/Controllers/AuthController.php';
        $auth = new \SellNow\Controllers\AuthController($twig, $db);
        $auth->dashboard();
        break;

    case '/products/add':
        require_once __DIR__ . '/../src/Controllers/ProductController.php';
        $prod = new \SellNow\Controllers\ProductController($twig, $db);
        if ($method === 'POST')
            $prod->store();
        else
            $prod->create();
        break;

    case '/cart':
        require_once __DIR__ . '/../src/Controllers/CartController.php';
        $cart = new \SellNow\Controllers\CartController($twig, $db);
        $cart->index();
        break;

    case '/cart/add':
        require_once __DIR__ . '/../src/Controllers/CartController.php';
        $cart = new \SellNow\Controllers\CartController($twig, $db);
        $cart->add();
        break;

    case '/cart/clear':
        require_once __DIR__ . '/../src/Controllers/CartController.php';
        $cart = new \SellNow\Controllers\CartController($twig, $db);
        $cart->clear();
        break;

    case '/checkout':
        require_once __DIR__ . '/../src/Controllers/CheckoutController.php';
        $checkout = new \SellNow\Controllers\CheckoutController($twig, $db);
        $checkout->index();
        break;

    case '/checkout/process':
        require_once __DIR__ . '/../src/Controllers/CheckoutController.php';
        $checkout = new \SellNow\Controllers\CheckoutController($twig, $db);
        $checkout->process();
        break;

    case '/payment':
        require_once __DIR__ . '/../src/Controllers/CheckoutController.php';
        $checkout = new \SellNow\Controllers\CheckoutController($twig, $db);
        $checkout->payment();
        break;

    case '/checkout/success':
        require_once __DIR__ . '/../src/Controllers/CheckoutController.php';
        $checkout = new \SellNow\Controllers\CheckoutController($twig, $db);
        $checkout->success();
        break;

    // Default: Dynamic Routes (Messy)
    default:
        // Check for public profile /username
        // Imperfect: Assuming anything else is a username
        $parts = explode('/', trim($uri, '/'));

        if (count($parts) == 1 && !empty($parts[0])) {
            require_once __DIR__ . '/../src/Controllers/PublicController.php';
            $pub = new \SellNow\Controllers\PublicController($twig, $db);
            $pub->profile($parts[0]);
        } elseif (count($parts) == 2 && $parts[1] == 'products') {
            // /username/products -> redirect to profile
            redirect('/' . $parts[0]);
        } else {
            http_response_code(404);
            echo "404 Not Found";
        }
        break;
}

