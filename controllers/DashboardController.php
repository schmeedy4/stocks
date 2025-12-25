<?php

declare(strict_types=1);

class DashboardController
{
    public function index(): void
    {
        require __DIR__ . '/../views/dashboard.php';
    }
}

