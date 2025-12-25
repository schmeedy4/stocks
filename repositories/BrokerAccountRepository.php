<?php

declare(strict_types=1);

class BrokerAccountRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::get_connection();
    }

    public function list_by_user(int $user_id): array
    {
        $stmt = $this->db->prepare('
            SELECT id, broker_code, name, currency
            FROM broker_account
            WHERE user_id = :user_id AND is_active = 1
            ORDER BY name ASC
        ');
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchAll();
    }
}

