<?php

namespace SellNow\Controllers;

class ProductController
{
    private $twig;
    private $db;

    public function __construct($twig, $db)
    {
        $this->twig = $twig;
        $this->db = $db;
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

        header("Location: /dashboard");
        exit;
    }
}
