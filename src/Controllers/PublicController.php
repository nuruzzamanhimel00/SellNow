<?php

namespace SellNow\Controllers;

use Twig\Environment;
use SellNow\Database\Connection;
use SellNow\Core\Request;
use SellNow\Core\Response;
use PDO;

class PublicController
{
    private Environment $twig;
    private PDO $db;

    public function __construct(Environment $twig, Connection $connection)
    {
        $this->twig = $twig;
        $this->db = $connection->getPdo();
    }

    public function home(Request $request): Response
    {
        $content = $this->twig->render('layouts/base.html.twig', [
            'content' => '<h1>Welcome to SellNow</h1>
                          <p>A platform for selling digital products.</p>
                          <a href="/login" class="btn btn-primary">Login</a>
                          <a href="/register" class="btn btn-success">Register</a>'
        ]);
        
        return Response::make($content);
    }

    public function profile($username)
    {
        // Raw SQL to find user
        // Imperfect: Inefficient separate queries
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :u");
        $stmt->execute(['u' => $username]);
        $user = $stmt->fetch(\PDO::FETCH_OBJ);

        if (!$user) {
            echo "User not found";
            return;
        }

        // Raw SQL to find products
        // Imperfect: SQL Injection possible if $user->id was tainted? (It's not here but shows intent)
        $pStmt = $this->db->query("SELECT * FROM products WHERE user_id = $user->id");
        $products = $pStmt->fetchAll(\PDO::FETCH_ASSOC);

        echo $this->twig->render('public/profile.html.twig', [
            'seller' => $user,
            'products' => $products
        ]);
    }
}
