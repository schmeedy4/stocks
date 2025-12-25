<?php

declare(strict_types=1);

function require_auth(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ?action=login');
        exit;
    }
}

function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

