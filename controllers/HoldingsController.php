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

        $result = $this->holdings_service->get_holdings($user_id);
        $holdings = $result['holdings'];
        $total_portfolio_value_usd = $result['total_portfolio_value_usd'];
        $total_todays_gain_usd = $result['total_todays_gain_usd'];
        $total_todays_gain_percent = $result['total_todays_gain_percent'];

        require __DIR__ . '/../views/holdings/list.php';
    }
}

