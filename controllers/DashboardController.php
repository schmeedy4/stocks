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

        // Calculate year summary
        $sum_proceeds_eur = '0.00';
        $tax_before_losses_eur = '0.00';
        $sum_negative_pnl_eur = '0.00';
        
        foreach ($sell_tax_totals as $tax_totals) {
            // Sum proceeds
            $proceeds = $tax_totals['total_sell_proceeds_eur'] ?? '0.00';
            $sum_proceeds_eur = bcadd($sum_proceeds_eur, $proceeds, 2);
            
            // Sum tax (tax before losses)
            $tax = $tax_totals['total_tax_eur'] ?? '0.00';
            $tax_before_losses_eur = bcadd($tax_before_losses_eur, $tax, 2);
            
            // Sum negative P/L (losses only) - these are already negative values
            $gain = $tax_totals['total_gain_eur'] ?? '0.00';
            if (bccomp($gain, '0.00', 2) < 0) {
                // Negative gain = loss, sum the negative values
                $sum_negative_pnl_eur = bcadd($sum_negative_pnl_eur, $gain, 2);
            }
        }
        
        // Total losses offset (absolute value of sum of negative P/L)
        $total_losses_offset_eur = bcsub('0.00', $sum_negative_pnl_eur, 2); // Convert negative to positive
        
        // Calculate final tax: max(0, tax_before_losses - loss_offset)
        $final_tax_eur = bcsub($tax_before_losses_eur, $total_losses_offset_eur, 2);
        if (bccomp($final_tax_eur, '0.00', 2) < 0) {
            $final_tax_eur = '0.00';
        }

        require __DIR__ . '/../views/dashboard.php';
    }
}

