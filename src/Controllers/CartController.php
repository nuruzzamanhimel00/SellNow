<?php

namespace SellNow\Controllers;

use Twig\Environment;
use SellNow\Database\Connection;
use PDO;

class CartController
{
    private Environment $twig;
    private PDO $db;

    public function __construct(Environment $twig, Connection $connection)
    {
        $this->twig = $twig;
        $this->db = $connection->getPdo();
    }

    public function index()
    {
        $cart = $_SESSION['cart'] ?? [];
        $total = 0;
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        echo $this->twig->render('cart/index.html.twig', [
            'cart' => $cart,
            'total' => $total
        ]);
    }

    public function add()
    {
        $id = $_POST['product_id'];
        $quantity = $_POST['quantity'];

        // Raw DB call
        $stmt = $this->db->prepare("SELECT * FROM products WHERE product_id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$product) {
            echo json_encode(['status' => 'error']);
            exit;
        }

        $_SESSION['cart'][] = [
            'product_id' => $product['product_id'],
            'title' => $product['title'],
            'price' => $product['price'],
            'quantity' => $quantity
        ];

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'count' => count($_SESSION['cart'])]);
        exit;
    }

    public function clear()
    {
        unset($_SESSION['cart']);
        header("Location: /cart");
        exit;
    }
}
