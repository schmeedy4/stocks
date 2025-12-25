<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;

final class AuthController
{
    public function __construct(private AuthService $authService)
    {
    }

    public function showLogin(array $flashes): void
    {
        $title = 'Login';
        $content = $this->renderLoginForm();
        require __DIR__ . '/../views/layout.php';
    }

    public function loginPost(string $email, string $password): void
    {
        $this->authService->login($email, $password);
    }

    public function logout(): void
    {
        $this->authService->logout();
    }

    private function renderLoginForm(): string
    {
        ob_start();
        require __DIR__ . '/../views/login.php';

        return (string) ob_get_clean();
    }
}
