<?php

declare(strict_types=1);

class TradeService
{
    private TradeRepository $trade_repo;
    private TradeLotRepository $lot_repo;
    private TradeLotAllocationRepository $allocation_repo;
    private InstrumentRepository $instrument_repo;

    public function __construct()
    {
        $this->trade_repo = new TradeRepository();
        $this->lot_repo = new TradeLotRepository();
        $this->allocation_repo = new TradeLotAllocationRepository();
        $this->instrument_repo = new InstrumentRepository();
    }

    public function list_trades(int $user_id, array $filters = []): array
    {
        return $this->trade_repo->list_by_user($user_id, $filters);
    }

    public function create_buy(int $user_id, array $input): int
    {
        $errors = $this->validate($input);

        if (!empty($errors)) {
            throw new ValidationException('Validation failed', $errors);
        }

        // Calculate total_value_eur = quantity * price_per_unit * fx_rate_to_eur
        $total_value_eur = $this->calculate_total_value(
            $input['quantity'],
            $input['price_per_unit'],
            $input['fx_rate_to_eur']
        );

        $fee_eur = isset($input['fee_eur']) && $input['fee_eur'] !== '' 
            ? $this->round_decimal($input['fee_eur'], 2)
            : '0.00';

        // Create trade
        $trade_data = [
            'broker_account_id' => $input['broker_account_id'] ?? null,
            'instrument_id' => (int) $input['instrument_id'],
            'trade_type' => 'BUY',
            'trade_date' => $input['trade_date'],
            'quantity' => $this->round_decimal($input['quantity'], 6),
            'price_per_unit' => $this->round_decimal($input['price_per_unit'], 8),
            'trade_currency' => strtoupper(trim($input['trade_currency'])),
            'fee_amount' => $input['fee_amount'] ?? null,
            'fee_currency' => isset($input['fee_currency']) ? strtoupper(trim($input['fee_currency'])) : null,
            'fx_rate_to_eur' => $this->round_decimal($input['fx_rate_to_eur'], 8),
            'total_value_eur' => $total_value_eur,
            'fee_eur' => $fee_eur,
            'notes' => $input['notes'] ?? null,
        ];

        $trade_id = $this->trade_repo->create_trade($user_id, $trade_data);

        // Create lot: cost_basis includes buy fee
        $cost_basis_eur = $this->add_decimals($total_value_eur, $fee_eur);
        $lot_data = [
            'buy_trade_id' => $trade_id,
            'instrument_id' => (int) $input['instrument_id'],
            'opened_date' => $input['trade_date'],
            'quantity_opened' => $this->round_decimal($input['quantity'], 6),
            'quantity_remaining' => $this->round_decimal($input['quantity'], 6),
            'cost_basis_eur' => $cost_basis_eur,
        ];

        $this->lot_repo->create_lot($user_id, $lot_data);

        return $trade_id;
    }

    public function create_sell_fifo(int $user_id, array $input): int
    {
        $errors = $this->validate($input);
        $instrument_id = (int) $input['instrument_id'];

        // Check user has enough open quantity
        $total_open_qty = $this->lot_repo->get_total_open_quantity($user_id, $instrument_id);
        $sell_qty = $this->round_decimal($input['quantity'], 6);
        
        if ($this->compare_decimals($total_open_qty, $sell_qty) < 0) {
            $errors['quantity'] = 'Insufficient quantity. Available: ' . $total_open_qty;
        }

        if (!empty($errors)) {
            throw new ValidationException('Validation failed', $errors);
        }

        // Calculate total_value_eur = quantity * price_per_unit * fx_rate_to_eur
        $total_value_eur = $this->calculate_total_value(
            $input['quantity'],
            $input['price_per_unit'],
            $input['fx_rate_to_eur']
        );

        $fee_eur = isset($input['fee_eur']) && $input['fee_eur'] !== '' 
            ? $this->round_decimal($input['fee_eur'], 2)
            : '0.00';

        // Create SELL trade
        $trade_data = [
            'broker_account_id' => $input['broker_account_id'] ?? null,
            'instrument_id' => $instrument_id,
            'trade_type' => 'SELL',
            'trade_date' => $input['trade_date'],
            'quantity' => $sell_qty,
            'price_per_unit' => $this->round_decimal($input['price_per_unit'], 8),
            'trade_currency' => strtoupper(trim($input['trade_currency'])),
            'fee_amount' => $input['fee_amount'] ?? null,
            'fee_currency' => isset($input['fee_currency']) ? strtoupper(trim($input['fee_currency'])) : null,
            'fx_rate_to_eur' => $this->round_decimal($input['fx_rate_to_eur'], 8),
            'total_value_eur' => $total_value_eur,
            'fee_eur' => $fee_eur,
            'notes' => $input['notes'] ?? null,
        ];

        $sell_trade_id = $this->trade_repo->create_trade($user_id, $trade_data);

        // Calculate net proceeds (after sell fee)
        $sell_proceeds_net_eur = $this->subtract_decimals($total_value_eur, $fee_eur);

        // Get open lots in FIFO order
        $lots = $this->lot_repo->list_open_lots_fifo($user_id, $instrument_id);
        
        $remaining_to_consume = $sell_qty;
        $allocations_created = 0;
        $total_proceeds_allocated = '0.00';
        $total_cost_allocated = '0.00';

        foreach ($lots as $lot) {
            if ($this->compare_decimals($remaining_to_consume, '0') <= 0) {
                break;
            }

            // How much to consume from this lot
            $qty_to_consume = $this->min_decimal(
                $remaining_to_consume,
                $lot->quantity_remaining
            );

            // Calculate proceeds for this allocation
            // proceeds_part = sell_proceeds_net_eur * (qty_consumed / sell_qty_total)
            $proceeds_part_eur = $this->multiply_decimals(
                $sell_proceeds_net_eur,
                $this->divide_decimals($qty_to_consume, $sell_qty)
            );

            // Calculate cost basis for this allocation
            // cost_basis_part = lot.cost_basis_eur * (qty_consumed / lot.quantity_opened)
            $cost_basis_part_eur = $this->multiply_decimals(
                $lot->cost_basis_eur,
                $this->divide_decimals($qty_to_consume, $lot->quantity_opened)
            );

            // Round to 2 decimals for storage
            $proceeds_part_eur = $this->round_decimal($proceeds_part_eur, 2);
            $cost_basis_part_eur = $this->round_decimal($cost_basis_part_eur, 2);

            // Calculate realized P/L
            $realized_pnl_eur = $this->subtract_decimals($proceeds_part_eur, $cost_basis_part_eur);
            $realized_pnl_eur = $this->round_decimal($realized_pnl_eur, 2);

            // Create allocation
            $allocation_data = [
                'sell_trade_id' => $sell_trade_id,
                'trade_lot_id' => $lot->id,
                'quantity_consumed' => $this->round_decimal($qty_to_consume, 6),
                'proceeds_eur' => $proceeds_part_eur,
                'cost_basis_eur' => $cost_basis_part_eur,
                'realized_pnl_eur' => $realized_pnl_eur,
            ];

            $this->allocation_repo->create_allocation($user_id, $allocation_data);

            // Update lot quantity_remaining
            $new_remaining = $this->subtract_decimals($lot->quantity_remaining, $qty_to_consume);
            $new_remaining = $this->round_decimal($new_remaining, 6);
            $this->lot_repo->update_quantity_remaining($user_id, $lot->id, $new_remaining);

            $remaining_to_consume = $this->subtract_decimals($remaining_to_consume, $qty_to_consume);
            $total_proceeds_allocated = $this->add_decimals($total_proceeds_allocated, $proceeds_part_eur);
            $total_cost_allocated = $this->add_decimals($total_cost_allocated, $cost_basis_part_eur);
            $allocations_created++;
        }

        return $sell_trade_id;
    }

    public function get_sell_with_allocations(int $user_id, int $sell_trade_id): array
    {
        $trade = $this->trade_repo->find_by_id($user_id, $sell_trade_id);
        if ($trade === null || $trade->trade_type !== 'SELL') {
            throw new NotFoundException('Sell trade not found');
        }

        $allocations_data = $this->allocation_repo->list_by_sell_trade($user_id, $sell_trade_id);

        return [
            'trade' => $trade,
            'allocations' => $allocations_data,
        ];
    }

    private function validate(array $input): array
    {
        $errors = [];

        // instrument_id required, must exist
        if (!isset($input['instrument_id']) || $input['instrument_id'] === '') {
            $errors['instrument_id'] = 'Instrument is required';
        } else {
            $instrument = $this->instrument_repo->find_by_id((int) $input['instrument_id']);
            if ($instrument === null) {
                $errors['instrument_id'] = 'Instrument not found';
            }
        }

        // trade_date required (YYYY-MM-DD)
        if (!isset($input['trade_date']) || $input['trade_date'] === '') {
            $errors['trade_date'] = 'Trade date is required';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['trade_date'])) {
            $errors['trade_date'] = 'Invalid date format';
        }

        // quantity > 0
        if (!isset($input['quantity']) || $input['quantity'] === '') {
            $errors['quantity'] = 'Quantity is required';
        } elseif ($this->compare_decimals($input['quantity'], '0') <= 0) {
            $errors['quantity'] = 'Quantity must be greater than 0';
        }

        // price_per_unit > 0
        if (!isset($input['price_per_unit']) || $input['price_per_unit'] === '') {
            $errors['price_per_unit'] = 'Price per unit is required';
        } elseif ($this->compare_decimals($input['price_per_unit'], '0') <= 0) {
            $errors['price_per_unit'] = 'Price per unit must be greater than 0';
        }

        // trade_currency required (3 letters)
        if (!isset($input['trade_currency']) || trim($input['trade_currency']) === '') {
            $errors['trade_currency'] = 'Trade currency is required';
        } elseif (strlen(trim($input['trade_currency'])) !== 3) {
            $errors['trade_currency'] = 'Trade currency must be 3 characters';
        }

        // fx_rate_to_eur > 0
        if (!isset($input['fx_rate_to_eur']) || $input['fx_rate_to_eur'] === '') {
            $errors['fx_rate_to_eur'] = 'FX rate to EUR is required';
        } elseif ($this->compare_decimals($input['fx_rate_to_eur'], '0') <= 0) {
            $errors['fx_rate_to_eur'] = 'FX rate must be greater than 0';
        }

        // fee_eur >= 0 (optional)
        if (isset($input['fee_eur']) && $input['fee_eur'] !== '') {
            if ($this->compare_decimals($input['fee_eur'], '0') < 0) {
                $errors['fee_eur'] = 'Fee cannot be negative';
            }
        }

        return $errors;
    }

    // Decimal-safe math functions
    private function calculate_total_value(string $quantity, string $price, string $fx_rate): string
    {
        $result = $this->multiply_decimals($quantity, $price);
        $result = $this->multiply_decimals($result, $fx_rate);
        return $this->round_decimal($result, 2);
    }

    private function round_decimal(string $value, int $precision): string
    {
        return number_format((float) $value, $precision, '.', '');
    }

    private function add_decimals(string $a, string $b): string
    {
        $result = bcadd($a, $b, 8);
        return $result;
    }

    private function subtract_decimals(string $a, string $b): string
    {
        $result = bcsub($a, $b, 8);
        return $result;
    }

    private function multiply_decimals(string $a, string $b): string
    {
        $result = bcmul($a, $b, 8);
        return $result;
    }

    private function divide_decimals(string $a, string $b): string
    {
        if ($b === '0' || $b === '0.00') {
            return '0';
        }
        $result = bcdiv($a, $b, 8);
        return $result;
    }

    private function compare_decimals(string $a, string $b): int
    {
        return bccomp($a, $b, 8);
    }

    private function min_decimal(string $a, string $b): string
    {
        return $this->compare_decimals($a, $b) <= 0 ? $a : $b;
    }
}

