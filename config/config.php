<?php

declare(strict_types=1);

return [
    'db' => [
        'dsn' => 'mysql:host=localhost;dbname=stocks;charset=utf8mb4',
        'user' => 'root',
        'password' => '',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ],
    ],
    'twelvedata' => [
        'api_key' => '0897fcea7769402ea8fbdb15debd650e',
    ],
];
