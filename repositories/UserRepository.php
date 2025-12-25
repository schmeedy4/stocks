<?php

declare(strict_types=1);

class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::get_connection();
    }

    public function find_by_email(string $email): ?User
    {
        $stmt = $this->db->prepare('
            SELECT id, email, password_hash, first_name, last_name, is_active
            FROM user
            WHERE email = :email
        ');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return new User(
            (int) $row['id'],
            $row['email'],
            $row['password_hash'],
            $row['first_name'],
            $row['last_name'],
            (bool) $row['is_active']
        );
    }

    public function create_user(
        string $email,
        string $password_plain,
        ?string $first_name = null,
        ?string $last_name = null
    ): int {
        $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare('
            INSERT INTO user (email, password_hash, first_name, last_name, is_active)
            VALUES (:email, :password_hash, :first_name, :last_name, 1)
        ');
        $stmt->execute([
            'email' => $email,
            'password_hash' => $password_hash,
            'first_name' => $first_name,
            'last_name' => $last_name,
        ]);

        return (int) $this->db->lastInsertId();
    }
}

