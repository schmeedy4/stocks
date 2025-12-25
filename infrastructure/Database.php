<?php

declare(strict_types=1);

class Database
{
    private static ?PDO $instance = null;

    public static function get_connection(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../config/config.php';
            $db_config = $config['db'];

            $options = $db_config['options'] ?? [];
            $options[PDO::ATTR_EMULATE_PREPARES] = false;
            
            self::$instance = new PDO(
                $db_config['dsn'],
                $db_config['user'],
                $db_config['password'],
                $options
            );
        }

        return self::$instance;
    }
}

