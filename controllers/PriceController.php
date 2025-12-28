<?php

declare(strict_types=1);

class PriceController
{
    private InstrumentPriceService $price_service;

    public function __construct()
    {
        require_once __DIR__ . '/../infrastructure/auth.php';
        require_auth();

        $this->price_service = new InstrumentPriceService();
    }

    public function list(): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        $portfolio_instruments = $this->price_service->list_portfolio_instruments($user_id);

        require __DIR__ . '/../views/prices/list.php';
    }

    public function update_prices_post(): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?action=prices');
            exit;
        }

        $price_date = date('Y-m-d'); // Use today's date
        $force_update = isset($_POST['force_update']) && $_POST['force_update'] === '1';

        $start_time = microtime(true);
        $result = $this->price_service->update_prices($user_id, $price_date, $force_update);
        $duration = microtime(true) - $start_time;

        $result['duration'] = $duration;
        $result['price_date'] = $price_date;

        // Store result in session to display after redirect (PRG pattern)
        $_SESSION['price_update_result'] = $result;

        header('Location: ?action=prices');
        exit;
    }

    public function update_last_5_days_post(): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?action=prices');
            exit;
        }

        $start_time = microtime(true);
        $result = $this->price_service->update_last_5_days($user_id);
        $duration = microtime(true) - $start_time;

        $result['duration'] = $duration;

        // Store result in session to display after redirect (PRG pattern)
        $_SESSION['price_update_5days_result'] = $result;

        header('Location: ?action=prices');
        exit;
    }
}

