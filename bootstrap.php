<?php

declare(strict_types=1);

use App\Infrastructure\Autoloader;

require __DIR__ . '/infrastructure/Autoloader.php';

Autoloader::register(__DIR__);

function require_auth(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: /?action=login');
        exit;
    }
}
