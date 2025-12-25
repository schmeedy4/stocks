<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use App\Models\User;

final class AuthService
{
    public function __construct(private UserRepository $userRepository)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function login(string $email, string $password): User
    {
        $user = $this->userRepository->findByEmail($email);
        if ($user === null || $user->passwordHash === '') {
            throw new \RuntimeException('Invalid credentials.');
        }

        if (!password_verify($password, $user->passwordHash)) {
            throw new \RuntimeException('Invalid credentials.');
        }

        $_SESSION['user_id'] = $user->id;

        return $user;
    }

    public function requireUserId(): int
    {
        if (!isset($_SESSION['user_id'])) {
            throw new \RuntimeException('Authentication required.');
        }

        return (int) $_SESSION['user_id'];
    }
}
