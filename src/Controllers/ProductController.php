<?php

namespace SellNow\Controllers;

use Twig\Environment;
use SellNow\Database\Connection;
use PDO;

class ProductController
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
        if (!isset($_SESSION['user_id'])) {
            header("Location: /login");
            exit;
        }

        // Fetch all products for the logged-in user
        $sql = "SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$_SESSION['user_id']]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo $this->twig->render('products/index.html.twig', [
            'products' => $products
        ]);
    }

    public function create()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /login");
            exit;
        }
        echo $this->twig->render('products/add.html.twig');
    }

    public function store()
    {
        if (!isset($_SESSION['user_id']))
            die("Unauthorized");

        $title = $_POST['title'];
        $price = $_POST['price'];
        $slug = strtolower(str_replace(' ', '-', $title)) . '-' . rand(1000, 9999);

        $uploadDir = __DIR__ . '/../../public/uploads/';

        $imagePath = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $name = time() . '_' . $_FILES['image']['name'];
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $name);
            $imagePath = 'uploads/' . $name;
        }

        $filePath = '';
        if (isset($_FILES['product_file']['error']) && $_FILES['product_file']['error'] == 0) {
            $name = time() . '_dl_' . $_FILES['product_file']['name'];
            move_uploaded_file($_FILES['product_file']['tmp_name'], $uploadDir . $name);
            $filePath = 'uploads/' . $name;
        }

        // Raw SQL
        $sql = "INSERT INTO products (user_id, title, slug, price, image_path, file_path) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $_SESSION['user_id'],
            $title,
            $slug,
            $price,
            $imagePath,
            $filePath
        ]);

        header("Location: /products");
        exit;
    }

    public function edit()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /login");
            exit;
        }

        // Get product ID from URL (assuming it's passed as ?id=123)
        $productId = $_GET['id'] ?? null;
        
        if (!$productId) {
            header("Location: /products");
            exit;
        }

        // Fetch product and verify ownership
        $sql = "SELECT * FROM products WHERE product_id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId, $_SESSION['user_id']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            header("Location: /products");
            exit;
        }

        echo $this->twig->render('products/edit.html.twig', [
            'product' => $product
        ]);
    }

    public function update()
    {
        if (!isset($_SESSION['user_id']))
            die("Unauthorized");

        $productId = $_POST['product_id'] ?? null;
        
        if (!$productId) {
            header("Location: /products");
            exit;
        }

        // Verify ownership
        $sql = "SELECT * FROM products WHERE product_id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId, $_SESSION['user_id']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            die("Unauthorized");
        }

        $title = $_POST['title'];
        $price = $_POST['price'];
        $description = $_POST['description'] ?? '';
        $slug = strtolower(str_replace(' ', '-', $title)) . '-' . rand(1000, 9999);

        $uploadDir = __DIR__ . '/../../public/uploads/';
        $imagePath = $product['image_path'];
        $filePath = $product['file_path'];

        // Handle new image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            // Delete old image if exists
            if ($imagePath && file_exists(__DIR__ . '/../../public/' . $imagePath)) {
                unlink(__DIR__ . '/../../public/' . $imagePath);
            }
            $name = time() . '_' . $_FILES['image']['name'];
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $name);
            $imagePath = 'uploads/' . $name;
        }

        // Handle new product file upload
        if (isset($_FILES['product_file']) && $_FILES['product_file']['error'] == 0) {
            // Delete old file if exists
            if ($filePath && file_exists(__DIR__ . '/../../public/' . $filePath)) {
                unlink(__DIR__ . '/../../public/' . $filePath);
            }
            $name = time() . '_dl_' . $_FILES['product_file']['name'];
            move_uploaded_file($_FILES['product_file']['tmp_name'], $uploadDir . $name);
            $filePath = 'uploads/' . $name;
        }

        // Update product
        $sql = "UPDATE products SET title = ?, slug = ?, price = ?, description = ?, image_path = ?, file_path = ? WHERE product_id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $title,
            $slug,
            $price,
            $description,
            $imagePath,
            $filePath,
            $productId,
            $_SESSION['user_id']
        ]);

        header("Location: /products");
        exit;
    }

    public function delete()
    {
        if (!isset($_SESSION['user_id']))
            die("Unauthorized");

        $productId = $_POST['product_id'] ?? null;
        
        if (!$productId) {
            header("Location: /products");
            exit;
        }

        // Fetch product to get file paths and verify ownership
        $sql = "SELECT * FROM products WHERE product_id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId, $_SESSION['user_id']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            die("Unauthorized");
        }

        // Delete files from server
        if ($product['image_path'] && file_exists(__DIR__ . '/../../public/' . $product['image_path'])) {
            unlink(__DIR__ . '/../../public/' . $product['image_path']);
        }
        if ($product['file_path'] && file_exists(__DIR__ . '/../../public/' . $product['file_path'])) {
            unlink(__DIR__ . '/../../public/' . $product['file_path']);
        }

        // Delete from database
        $sql = "DELETE FROM products WHERE product_id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId, $_SESSION['user_id']]);

        header("Location: /products");
        exit;
    }
}
