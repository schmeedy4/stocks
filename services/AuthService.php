<?php

declare(strict_types=1);

class AuthService
{
    private UserRepository $user_repo;

    public function __construct()
    {
        $this->user_repo = new UserRepository();
    }

    public function login(string $email, string $password): User
    {
        $user = $this->user_repo->find_by_email($email);

        if ($user === null) {
            throw new ValidationException('Invalid email or password');
        }

        if ($user->password_hash === null) {
            throw new ValidationException('Invalid email or password');
        }

        if (!password_verify($password, $user->password_hash)) {
            throw new ValidationException('Invalid email or password');
        }

        if (!$user->is_active) {
            throw new ValidationException('Account is inactive');
        }

        return $user;
    }

    public function logout(): void
    {
        // Session clearing is handled by controller
    }
}

