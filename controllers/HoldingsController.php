<?php

declare(strict_types=1);

class HoldingsController
{
    private HoldingsService $holdings_service;

    public function __construct()
    {
        $this->holdings_service = new HoldingsService();
    }

    public function list(): void
    {
        require_once __DIR__ . '/../infrastructure/auth.php';
        require_auth();

        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        $holdings = $this->holdings_service->get_holdings($user_id);

        require __DIR__ . '/../views/holdings/list.php';
    }
}

