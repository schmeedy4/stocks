<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use PDO;

final class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM user WHERE email = :email AND is_active = 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return new User(
            id: (int) $row['id'],
            email: $row['email'],
            passwordHash: $row['password_hash'] ?? '',
            firstName: $row['first_name'],
            lastName: $row['last_name'],
            isActive: (bool) $row['is_active'],
        );
    }
}
