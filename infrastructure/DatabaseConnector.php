<?php

declare(strict_types=1);

namespace App\Infrastructure;

use PDO;
use PDOException;

final class DatabaseConnector
{
    public static function connect(array $config): PDO
    {
        if (!isset($config['dsn'], $config['user'], $config['password'])) {
            throw new PDOException('Invalid database configuration.');
        }

        return new PDO(
            $config['dsn'],
            $config['user'],
            $config['password'],
            $config['options'] ?? []
        );
    }
}
