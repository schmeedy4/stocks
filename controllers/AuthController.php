<?php

declare(strict_types=1);

class AuthController
{
    private AuthService $auth_service;

    public function __construct()
    {
        $this->auth_service = new AuthService();
    }

    public function show_login(): void
    {
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);

        require __DIR__ . '/../views/auth/login.php';
    }

    public function login_post(): void
    {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $_SESSION['login_error'] = 'Email and password are required';
            header('Location: ?action=login');
            exit;
        }

        try {
            $user = $this->auth_service->login($email, $password);
            $_SESSION['user_id'] = $user->id;
            header('Location: ?action=dashboard');
            exit;
        } catch (ValidationException $e) {
            $_SESSION['login_error'] = $e->getMessage();
            header('Location: ?action=login');
            exit;
        }
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
        header('Location: ?action=login');
        exit;
    }
}

