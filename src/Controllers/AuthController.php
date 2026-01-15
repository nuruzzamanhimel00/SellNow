<?php

namespace SellNow\Controllers;

use SellNow\Core\Request;
use SellNow\Core\Response;
use SellNow\Services\AuthService;
use SellNow\Security\CsrfToken;
use Twig\Environment;

class AuthController
{
    private Environment $twig;
    private AuthService $authService;
    private CsrfToken $csrfToken;

    public function __construct(Environment $twig, AuthService $authService, CsrfToken $csrfToken)
    {
        $this->twig = $twig;
        $this->authService = $authService;
        $this->csrfToken = $csrfToken;
    }

    public function loginForm(Request $request): Response
    {
        if (isset($_SESSION['user_id'])) {
            return Response::redirect('/dashboard');
        }
        
        $content = $this->twig->render('auth/login.html.twig', [
            'error' => $request->query('error'),
            'msg' => $request->query('msg')
        ]);
        
        return Response::make($content);
    }

    public function login(Request $request): Response
    {
        $email = $request->post('email', '');
        $password = $request->post('password', '');

        $result = $this->authService->login($email, $password);

        if ($result['success']) {
            $_SESSION['user_id'] = $result['user']['id'];
            $_SESSION['username'] = $result['user']['username'];
            
            // Regenerate CSRF token after login
            $this->csrfToken->regenerate();
            
            return Response::redirect('/dashboard');
        }

        return Response::redirect('/login?error=' . urlencode($result['error']));
    }

    public function registerForm(Request $request): Response
    {
        $content = $this->twig->render('auth/register.html.twig', [
            'error' => $request->query('error')
        ]);
        return Response::make($content);
    }

    public function register(Request $request): Response
    {
        $data = [
            'email' => $request->post('email'),
            'username' => $request->post('username'),
            'full_name' => $request->post('fullname'),
            'password' => $request->post('password')
        ];
        
        $result = $this->authService->register($data);

        if ($result['success']) {
            return Response::redirect('/login?msg=' . urlencode('Registration successful! Please login.'));
        }

        // Show errors
        $errors = $result['errors'];
        $errorMessage = '';
        foreach ($errors as $field => $fieldErrors) {
            $errorMessage .= implode(', ', $fieldErrors) . ' ';
        }

        return Response::redirect('/register?error=' . urlencode(trim($errorMessage)));
    }

    public function logout(Request $request): Response
    {
        session_destroy();
        return Response::redirect('/');
    }

    public function dashboard(Request $request): Response
    {
        if (!isset($_SESSION['user_id'])) {
            return Response::redirect('/login');
        }

        $content = $this->twig->render('dashboard.html.twig', [
            'username' => $_SESSION['username'] ?? 'User'
        ]);
        
        return Response::make($content);
    }

    public function editProfile(Request $request): Response
    {
        if (!isset($_SESSION['user_id'])) {
            return Response::redirect('/login');
        }

        // Get current user data
        $user = $this->authService->getUserById($_SESSION['user_id']);

        if (!$user) {
            return Response::redirect('/login');
        }

        $content = $this->twig->render('auth/profile-edit.html.twig', [
            'user' => $user,
            'msg' => $request->query('msg'),
            'error' => $request->query('error')
        ]);
        
        return Response::make($content);
    }

    public function updateProfile(Request $request): Response
    {
        if (!isset($_SESSION['user_id'])) {
            return Response::redirect('/login');
        }

        $data = [
            'id' => $_SESSION['user_id'],
            'email' => $request->post('email'),
            'username' => $request->post('username'),
            'full_name' => $request->post('full_name'),
            'current_password' => $request->post('current_password'),
            'new_password' => $request->post('new_password'),
            'confirm_password' => $request->post('confirm_password')
        ];

        $result = $this->authService->updateProfile($data);

        if ($result['success']) {
            // Update session if username changed
            if (isset($result['user']['username'])) {
                $_SESSION['username'] = $result['user']['username'];
            }
            return Response::redirect('/profile/edit?msg=' . urlencode('Profile updated successfully!'));
        }

        // Show errors
        $errors = $result['errors'];
        $errorMessage = '';
        foreach ($errors as $field => $fieldErrors) {
            $errorMessage .= implode(', ', $fieldErrors) . ' ';
        }

        return Response::redirect('/profile/edit?error=' . urlencode(trim($errorMessage)));
    }
}
