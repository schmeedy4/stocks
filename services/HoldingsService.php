<?php

declare(strict_types=1);

class HoldingsService
{
    private TradeLotRepository $lot_repo;
    private InstrumentPriceDailyRepository $price_repo;
    private InstrumentRepository $instrument_repo;
    private TradeRepository $trade_repo;

    public function __construct()
    {
        $this->lot_repo = new TradeLotRepository();
        $this->price_repo = new InstrumentPriceDailyRepository();
        $this->instrument_repo = new InstrumentRepository();
        $this->trade_repo = new TradeRepository();
    }

    /**
     * Get all holdings (open positions) with their price data.
     * Returns array of holdings, each with instrument info, lots, shares, cost, prices, etc.
     * All portfolio metrics are calculated in USD.
     */
    public function get_holdings(int $user_id): array
    {
        // Get all open lots grouped by instrument
        $lots_by_instrument = $this->lot_repo->get_all_open_lots_grouped_by_instrument($user_id);

        $holdings = [];

        foreach ($lots_by_instrument as $instrument_id => $lots) {
            // Calculate total shares and cost basis
            $shares = '0';
            $total_cost_basis_eur = '0.00';
            $total_cost_basis_usd = '0.00';
            
            // Fetch buy trades for all lots to get price_per_unit, trade_currency, and fx_rate_to_eur
            $buy_trades = [];
            foreach ($lots as $lot) {
                if (!isset($buy_trades[$lot->buy_trade_id])) {
                    $buy_trade = $this->trade_repo->find_by_id($user_id, $lot->buy_trade_id);
                    if ($buy_trade !== null) {
                        $buy_trades[$lot->buy_trade_id] = $buy_trade;
                    }
                }
            }

            // Calculate total shares, cost basis in EUR and USD
            $total_price_weighted = '0';
            
            foreach ($lots as $lot) {
                $shares = $this->add_decimals($shares, $lot->quantity_remaining);
                // Calculate remaining cost basis proportionally
                $lot_remaining_cost_eur = $this->calculate_remaining_cost_basis($lot);
                $total_cost_basis_eur = $this->add_decimals($total_cost_basis_eur, $lot_remaining_cost_eur);
                
                // Calculate weighted average price_per_unit and cost in USD
                if (isset($buy_trades[$lot->buy_trade_id])) {
                    $buy_trade = $buy_trades[$lot->buy_trade_id];
                    
                    // Weighted price_per_unit: price_per_unit * quantity_remaining
                    $weighted_price = $this->multiply_decimals($buy_trade->price_per_unit, $lot->quantity_remaining);
                    $total_price_weighted = $this->add_decimals($total_price_weighted, $weighted_price);
                    
                    // Calculate USD cost basis from original trade amounts
                    // cost_basis_usd = (lot_remaining_qty / lot_opened_qty) * original_cost_usd
                    // where original_cost_usd = (quantity * price_per_unit + fee) in original currency, converted to USD if needed
                    $quantity_ratio = $this->divide_decimals($lot->quantity_remaining, $lot->quantity_opened);
                    
                    if ($buy_trade->trade_currency === 'USD') {
                        // For USD trades: cost_usd = cost_eur / fx_rate_to_eur
                        // fx_rate_to_eur converts USD to EUR, so dividing EUR by rate gives USD
                        $fx_rate_to_eur = $buy_trade->fx_rate_to_eur;
                        if ($this->compare_decimals($fx_rate_to_eur, '0') > 0) {
                            $lot_cost_usd = $this->divide_decimals($lot_remaining_cost_eur, $fx_rate_to_eur);
                            $total_cost_basis_usd = $this->add_decimals($total_cost_basis_usd, $lot_cost_usd);
                        } else {
                            // Invalid fx_rate, fallback to EUR value (shouldn't happen)
                            $total_cost_basis_usd = $this->add_decimals($total_cost_basis_usd, $lot_remaining_cost_eur);
                        }
                    } elseif ($buy_trade->trade_currency === 'EUR') {
                        // For EUR trades, we need EUR/USD rate
                        // Since we don't have historical EUR/USD rates stored, we'll need to handle this
                        // For now, we'll convert using fx_rate_to_eur = 1.0 for EUR trades
                        // In practice, EUR trades converted to USD would need a separate EUR/USD rate
                        // This is a limitation - ideally we'd store EUR/USD rate or calculate from price data
                        // For now, leave EUR cost as-is (will be wrong if prices are in USD)
                        // TODO: Handle EUR->USD conversion properly when EUR/USD rate is available
                        $total_cost_basis_usd = $this->add_decimals($total_cost_basis_usd, $lot_remaining_cost_eur);
                    } else {
                        // For other currencies, convert via EUR first, then to USD if possible
                        // This is an approximation - ideally we'd need direct USD/trade_currency rate
                        $fx_rate_to_eur = $buy_trade->fx_rate_to_eur;
                        if ($this->compare_decimals($fx_rate_to_eur, '0') > 0) {
                            // Convert to original currency, assume it's close to USD (approximation)
                            $lot_cost_original = $this->divide_decimals($lot_remaining_cost_eur, $fx_rate_to_eur);
                            $total_cost_basis_usd = $this->add_decimals($total_cost_basis_usd, $lot_cost_original);
                        } else {
                            $total_cost_basis_usd = $this->add_decimals($total_cost_basis_usd, $lot_remaining_cost_eur);
                        }
                    }
                }
            }

            // Skip if no shares (should not happen, but be safe)
            if ($this->compare_decimals($shares, '0') <= 0) {
                continue;
            }

            // Get instrument info
            $instrument = $this->instrument_repo->find_by_id($instrument_id);
            if ($instrument === null) {
                continue;
            }

            // Get latest and previous prices (prices from API are in USD)
            $price_data = $this->price_repo->get_latest_and_previous_price($user_id, $instrument_id);
            $latest_price = $price_data['latest'];
            $previous_price = $price_data['previous'];

            // Get price values in USD
            $close_price_usd = $latest_price !== null ? $latest_price->close_price : '0';
            $previous_close_price_usd = $previous_price !== null ? $previous_price->close_price : '0';
            $price_date = $latest_price !== null ? $latest_price->price_date : null;
            $price_currency = $latest_price !== null ? $latest_price->currency : 'USD';

            // If prices are not in USD, we'd need to convert, but for now assume USD
            // (API typically returns USD for stocks)

            // Calculate price change in USD
            $change_usd = $this->subtract_decimals($close_price_usd, $previous_close_price_usd);
            $change_percent = '0.00';
            if ($previous_price !== null && $this->compare_decimals($previous_close_price_usd, '0') > 0) {
                $change_percent = $this->multiply_decimals(
                    $this->divide_decimals($change_usd, $previous_close_price_usd),
                    '100'
                );
                $change_percent = $this->round_decimal($change_percent, 2);
            }

            // Calculate value in USD (shares * price_usd)
            $value_usd = $this->multiply_decimals($shares, $close_price_usd);
            $value_usd = $this->round_decimal($value_usd, 2);

            // Calculate average cost per share from price_per_unit (weighted average)
            // This is in the original trade currency, but should be USD for most cases
            $avg_cost = '0.00';
            if ($this->compare_decimals($shares, '0') > 0) {
                $avg_cost = $this->divide_decimals($total_price_weighted, $shares);
                $avg_cost = $this->round_decimal($avg_cost, 2);
            }
            
            // Finalize USD cost basis
            $total_cost_basis_usd = $this->round_decimal($total_cost_basis_usd, 2);

            // Calculate today's gain in USD
            $todays_gain_usd = $this->multiply_decimals($change_usd, $shares);
            $todays_gain_usd = $this->round_decimal($todays_gain_usd, 2);

            // Today's Gain (%) = (price_today - price_previous) / price_previous * 100
            // This is the same as change_percent
            $todays_gain_percent = $change_percent;

            // Calculate total change in USD (Value - Cost)
            $total_change_usd = $this->subtract_decimals($value_usd, $total_cost_basis_usd);
            $total_change_percent = '0.00';
            if ($this->compare_decimals($total_cost_basis_usd, '0') > 0) {
                $total_change_percent = $this->multiply_decimals(
                    $this->divide_decimals($total_change_usd, $total_cost_basis_usd),
                    '100'
                );
                $total_change_percent = $this->round_decimal($total_change_percent, 2);
            }

            // Calculate sell 100% tax (Slovenia) - calculate in EUR first, then convert to USD
            // Tax calculation needs price in EUR, but we have USD price
            // Since we don't have current EUR/USD exchange rate, we'll approximate using fx_rate_to_eur from buy trades
            // Note: This uses historical rates, so it's an approximation
            $sell_date = $price_date ?? date('Y-m-d');
            $close_price_for_tax_eur = $close_price_usd;
            
            // Try to estimate EUR/USD rate from buy trades (if any are USD)
            // fx_rate_to_eur converts FROM trade_currency TO EUR
            // For USD trades: fx_rate_to_eur = USD->EUR rate at buy time
            // To convert current USD price to EUR: price_eur = price_usd * fx_rate_usd_to_eur
            $avg_fx_rate_usd_to_eur = null;
            if ($price_currency === 'USD') {
                $fx_rate_sum = '0';
                $fx_rate_count = 0;
                foreach ($buy_trades as $buy_trade) {
                    if ($buy_trade->trade_currency === 'USD' && $this->compare_decimals($buy_trade->fx_rate_to_eur, '0') > 0) {
                        $fx_rate_sum = $this->add_decimals($fx_rate_sum, $buy_trade->fx_rate_to_eur);
                        $fx_rate_count++;
                    }
                }
                
                // Average fx_rate_to_eur to estimate USD->EUR conversion
                if ($fx_rate_count > 0 && $this->compare_decimals($fx_rate_sum, '0') > 0) {
                    $avg_fx_rate_usd_to_eur = $this->divide_decimals($fx_rate_sum, (string)$fx_rate_count);
                    // Convert USD price to EUR: price_eur = price_usd * fx_rate_usd_to_eur
                    $close_price_for_tax_eur = $this->multiply_decimals($close_price_usd, $avg_fx_rate_usd_to_eur);
                    $close_price_for_tax_eur = $this->round_decimal($close_price_for_tax_eur, 6);
                }
            }
            // If price is already in EUR or we can't determine rate, use price as-is
            
            $sell_100_tax_eur = $this->compute_sell_100_tax_si($lots, $close_price_for_tax_eur, $sell_date);
            
            // Convert tax from EUR to USD
            // fx_rate_to_eur converts USD->EUR, so to convert EUR->USD: tax_usd = tax_eur / fx_rate_to_eur
            $sell_100_tax_usd = $sell_100_tax_eur;
            if ($avg_fx_rate_usd_to_eur !== null && $this->compare_decimals($avg_fx_rate_usd_to_eur, '0') > 0) {
                $sell_100_tax_usd = $this->divide_decimals($sell_100_tax_eur, $avg_fx_rate_usd_to_eur);
                $sell_100_tax_usd = $this->round_decimal($sell_100_tax_usd, 2);
            } else {
                // If we can't determine rate, assume 1:1 (not ideal, but better than nothing)
                $sell_100_tax_usd = $this->round_decimal($sell_100_tax_eur, 2);
            }

            // Calculate no tax date
            $no_tax_date = $this->compute_no_tax_date($lots, $sell_date);

            $holdings[] = [
                'instrument_id' => $instrument_id,
                'instrument' => $instrument,
                'lots' => $lots,
                'shares' => $shares,
                'cost_basis_eur' => $this->round_decimal($total_cost_basis_eur, 2), // For tax calculations
                'cost_basis_usd' => $total_cost_basis_usd,
                'close_price_usd' => $close_price_usd,
                'previous_close_price_usd' => $previous_close_price_usd,
                'change_usd' => $this->round_decimal($change_usd, 2),
                'change_percent' => $change_percent,
                'avg_cost' => $avg_cost,
                'value_usd' => $value_usd,
                'todays_gain_usd' => $todays_gain_usd,
                'todays_gain_percent' => $todays_gain_percent,
                'total_change_usd' => $this->round_decimal($total_change_usd, 2),
                'total_change_percent' => $total_change_percent,
                'sell_100_tax_eur' => $sell_100_tax_eur,
                'sell_100_tax_usd' => $sell_100_tax_usd,
                'no_tax_date' => $no_tax_date,
                'price_date' => $price_date,
            ];
        }

        // Calculate portfolio total value and weights (in USD)
        $total_portfolio_value_usd = '0.00';
        foreach ($holdings as $holding) {
            $total_portfolio_value_usd = $this->add_decimals($total_portfolio_value_usd, $holding['value_usd']);
        }
        $total_portfolio_value_usd = $this->round_decimal($total_portfolio_value_usd, 2);

        // Add weight to each holding
        foreach ($holdings as &$holding) {
            $weight = '0.00';
            if ($this->compare_decimals($total_portfolio_value_usd, '0') > 0) {
                $weight = $this->multiply_decimals(
                    $this->divide_decimals($holding['value_usd'], $total_portfolio_value_usd),
                    '100'
                );
                $weight = $this->round_decimal($weight, 2);
            }
            $holding['weight_percent'] = $weight;
        }
        unset($holding);

        return $holdings;
    }

    /**
     * Calculate remaining cost basis for a lot proportionally.
     * remaining_cost = cost_basis_eur * (quantity_remaining / quantity_opened)
     */
    private function calculate_remaining_cost_basis(TradeLot $lot): string
    {
        if ($this->compare_decimals($lot->quantity_opened, '0') <= 0) {
            return '0.00';
        }
        $ratio = $this->divide_decimals($lot->quantity_remaining, $lot->quantity_opened);
        $remaining_cost = $this->multiply_decimals($lot->cost_basis_eur, $ratio);
        return $this->round_decimal($remaining_cost, 2);
    }

    /**
     * Compute Slovenia tax if selling 100% of lots today at given price.
     * Returns total tax in EUR.
     * Note: price should be in EUR for accurate tax calculation.
     */
    public function compute_sell_100_tax_si(array $lots, string $sell_price, string $sell_date): string
    {
        if (empty($lots)) {
            return '0.00';
        }

        // Calculate total shares
        $total_shares = '0';
        foreach ($lots as $lot) {
            $total_shares = $this->add_decimals($total_shares, $lot->quantity_remaining);
        }

        if ($this->compare_decimals($total_shares, '0') <= 0) {
            return '0.00';
        }

        // Total proceeds = total_shares * sell_price (price should be in EUR for tax)
        // Note: If price is in USD, we'd need to convert, but for now assume EUR
        $total_proceeds_eur = $this->multiply_decimals($total_shares, $sell_price);
        $total_proceeds_eur = $this->round_decimal($total_proceeds_eur, 2);

        $sell_date_obj = new \DateTimeImmutable($sell_date);
        $sell_year = (int) $sell_date_obj->format('Y');

        $total_tax = '0.00';

        // Allocate proceeds proportionally to each lot
        foreach ($lots as $lot) {
            // Proceeds for this lot = total_proceeds * (lot_qty / total_qty)
            $lot_proceeds_ratio = $this->divide_decimals($lot->quantity_remaining, $total_shares);
            $lot_proceeds_eur = $this->multiply_decimals($total_proceeds_eur, $lot_proceeds_ratio);
            $lot_proceeds_eur = $this->round_decimal($lot_proceeds_eur, 2);

            // Cost basis for this lot (remaining portion) - already in EUR
            $lot_cost_eur = $this->calculate_remaining_cost_basis($lot);

            // Gain = proceeds - cost
            $gain_eur = $this->subtract_decimals($lot_proceeds_eur, $lot_cost_eur);

            // Normirani stroški
            $norm_raw = $this->multiply_decimals('0.01', $lot_cost_eur);
            $norm_raw = $this->add_decimals($norm_raw, $this->multiply_decimals('0.01', $lot_proceeds_eur));
            $gain_non_neg = $this->compare_decimals($gain_eur, '0') > 0 ? $gain_eur : '0.00';
            $norm_eur = $this->compare_decimals($norm_raw, $gain_non_neg) <= 0 ? $norm_raw : $gain_non_neg;
            $norm_eur = $this->round_decimal($norm_eur, 2);

            // Tax base = max(0, gain - norm)
            $tax_base_eur = $this->subtract_decimals($gain_eur, $norm_eur);
            if ($this->compare_decimals($tax_base_eur, '0') < 0) {
                $tax_base_eur = '0.00';
            } else {
                $tax_base_eur = $this->round_decimal($tax_base_eur, 2);
            }

            // Holding years
            $buy_date_obj = new \DateTimeImmutable($lot->opened_date);
            $holding_years = $buy_date_obj->diff($sell_date_obj)->y;

            // Tax rate
            $tax_rate_percent = $this->get_slovenia_tax_rate($sell_year, $holding_years);

            // Tax = tax_base * rate
            $tax_eur = $this->multiply_decimals($tax_base_eur, (string)($tax_rate_percent / 100));
            $tax_eur = $this->round_decimal($tax_eur, 2);

            $total_tax = $this->add_decimals($total_tax, $tax_eur);
        }

        return $this->round_decimal($total_tax, 2);
    }

    /**
     * Compute earliest tax-free date (buy_date + 15 years) among remaining lots.
     * For sales 2022+, holdings become tax-free after 15 years.
     * Returns date string (YYYY-MM-DD), "already" if all lots are tax-free, or null if no lots.
     */
    public function compute_no_tax_date(array $lots, string $sell_date): ?string
    {
        if (empty($lots)) {
            return null;
        }

        $sell_date_obj = new \DateTimeImmutable($sell_date);
        $earliest_tax_free_date = null;
        $all_tax_free = true;

        foreach ($lots as $lot) {
            $buy_date_obj = new \DateTimeImmutable($lot->opened_date);
            // Tax-free date = buy_date + 15 years (for sales 2022+)
            $tax_free_date = $buy_date_obj->modify('+15 years');

            if ($earliest_tax_free_date === null || $tax_free_date < $earliest_tax_free_date) {
                $earliest_tax_free_date = $tax_free_date;
            }

            // Check if this lot is already tax-free (tax_free_date <= sell_date)
            if ($tax_free_date > $sell_date_obj) {
                $all_tax_free = false;
            }
        }

        // If all lots are already tax-free, return "already"
        if ($all_tax_free && $earliest_tax_free_date !== null) {
            return 'already';
        }

        return $earliest_tax_free_date->format('Y-m-d');
    }

    /**
     * Get Slovenia tax rate based on sell year and holding years.
     * (Reuses logic from TradeService)
     */
    private function get_slovenia_tax_rate(int $sell_year, int $holding_years): float
    {
        if ($sell_year <= 2019) {
            if ($holding_years >= 20) return 0.0;
            if ($holding_years >= 15) return 5.0;
            if ($holding_years >= 10) return 10.0;
            if ($holding_years >= 5) return 15.0;
            return 25.0;
        } elseif ($sell_year >= 2020 && $sell_year <= 2021) {
            if ($holding_years >= 20) return 0.0;
            if ($holding_years >= 15) return 10.0;
            if ($holding_years >= 10) return 15.0;
            if ($holding_years >= 5) return 20.0;
            return 27.5;
        } else {
            // sell_year >= 2022
            if ($holding_years >= 20) return 0.0;
            if ($holding_years >= 15) return 0.0;
            if ($holding_years >= 10) return 15.0;
            if ($holding_years >= 5) return 20.0;
            return 25.0;
        }
    }

    // Decimal-safe math functions (same as TradeService)
    private function round_decimal(string $value, int $precision): string
    {
        return number_format((float) $value, $precision, '.', '');
    }

    private function add_decimals(string $a, string $b): string
    {
        return bcadd($a, $b, 8);
    }

    private function subtract_decimals(string $a, string $b): string
    {
        return bcsub($a, $b, 8);
    }

    private function multiply_decimals(string $a, string $b): string
    {
        return bcmul($a, $b, 8);
    }

    private function divide_decimals(string $a, string $b): string
    {
        if ($b === '0' || $b === '0.00') {
            return '0';
        }
        return bcdiv($a, $b, 8);
    }

    private function compare_decimals(string $a, string $b): int
    {
        return bccomp($a, $b, 8);
    }
}
