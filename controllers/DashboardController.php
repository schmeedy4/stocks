<?php

declare(strict_types=1);

class DashboardController
{
    private TradeService $trade_service;
    private InstrumentRepository $instrument_repo;

    public function __construct()
    {
        require_once __DIR__ . '/../infrastructure/auth.php';
        require_auth();

        $this->trade_service = new TradeService();
        $this->instrument_repo = new InstrumentRepository();
    }

    public function index(): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        // Get year filter (default to current year)
        $selected_year = isset($_GET['year']) && $_GET['year'] !== '' 
            ? (int) $_GET['year'] 
            : (int) date('Y');
        
        // Get min year for year selector
        $min_year = $this->trade_service->get_min_year($user_id);
        if ($min_year === null) {
            $min_year = (int) date('Y');
        }

        // Get SELL trades for selected year
        $filters = ['year' => $selected_year];
        $all_trades = $this->trade_service->list_trades($user_id, $filters);
        $sell_trades = [];
        foreach ($all_trades as $trade) {
            if ($trade->trade_type === 'SELL') {
                $sell_trades[] = $trade;
            }
        }

        // Get instruments for display
        $instruments = [];
        foreach ($sell_trades as $trade) {
            if (!isset($instruments[$trade->instrument_id])) {
                $instruments[$trade->instrument_id] = $this->instrument_repo->find_by_id($trade->instrument_id);
            }
        }

        // Get tax totals for SELL trades
        $sell_tax_totals = [];
        foreach ($sell_trades as $trade) {
            try {
                $tax_totals = $this->trade_service->get_sell_tax_totals($user_id, $trade->id);
                if ($tax_totals !== null) {
                    $sell_tax_totals[$trade->id] = $tax_totals;
                }
            } catch (\Exception $e) {
                // If tax calculation fails, skip this trade (should not happen, but be safe)
                continue;
            }
        }

        require __DIR__ . '/../views/dashboard.php';
    }
}

