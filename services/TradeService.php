<?php

declare(strict_types=1);

class TradeService
{
    private TradeRepository $trade_repo;
    private TradeLotRepository $lot_repo;
    private TradeLotAllocationRepository $allocation_repo;
    private InstrumentRepository $instrument_repo;
    private TradeDocumentRepository $trade_document_repo;

    public function __construct()
    {
        $this->trade_repo = new TradeRepository();
        $this->lot_repo = new TradeLotRepository();
        $this->allocation_repo = new TradeLotAllocationRepository();
        $this->instrument_repo = new InstrumentRepository();
        $this->trade_document_repo = new TradeDocumentRepository();
    }

    public function list_trades(int $user_id, array $filters = []): array
    {
        return $this->trade_repo->list_by_user($user_id, $filters);
    }

    /**
     * Get minimum year from all trades for the user.
     */
    public function get_min_year(int $user_id): ?int
    {
        return $this->trade_repo->get_min_year($user_id);
    }

    public function create_buy(int $user_id, array $input): int
    {
        $errors = $this->validate($input);

        if (!empty($errors)) {
            throw new ValidationException('Validation failed', $errors);
        }

        // Convert broker_fx_rate to fx_rate_to_eur
        // Store with high precision (12 decimals) for accuracy
        $trade_currency = strtoupper(trim($input['trade_currency']));
        $fx_rate_to_eur = '1.000000000000';
        if ($trade_currency !== 'EUR' && isset($input['broker_fx_rate']) && $input['broker_fx_rate'] !== '') {
            $fx_rate_to_eur = $this->divide_decimals('1', $input['broker_fx_rate']);
            $fx_rate_to_eur = $this->round_decimal($fx_rate_to_eur, 12);
        }

        // Calculate price_eur = round(price_per_unit * fx_rate_to_eur, 8)
        $price_eur = $this->multiply_decimals($input['price_per_unit'], $fx_rate_to_eur);
        $price_eur = $this->round_decimal($price_eur, 8);

        // Calculate total_value_eur = round(quantity * price_eur, 2)
        $total_value_eur = $this->multiply_decimals($input['quantity'], $price_eur);
        $total_value_eur = $this->round_decimal($total_value_eur, 2);

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
            'price_eur' => $price_eur,
            'trade_currency' => $trade_currency,
            'fee_amount' => $input['fee_amount'] ?? null,
            'fee_currency' => isset($input['fee_currency']) ? strtoupper(trim($input['fee_currency'])) : null,
            'fx_rate_to_eur' => $fx_rate_to_eur,
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

        // Link documents if provided
        if (isset($input['document_ids']) && is_array($input['document_ids'])) {
            $this->link_documents($user_id, $trade_id, $input['document_ids']);
        }

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

        // Convert broker_fx_rate to fx_rate_to_eur
        // Store with high precision (12 decimals) for accuracy
        $trade_currency = strtoupper(trim($input['trade_currency']));
        $fx_rate_to_eur = '1.000000000000';
        if ($trade_currency !== 'EUR' && isset($input['broker_fx_rate']) && $input['broker_fx_rate'] !== '') {
            $fx_rate_to_eur = $this->divide_decimals('1', $input['broker_fx_rate']);
            $fx_rate_to_eur = $this->round_decimal($fx_rate_to_eur, 12);
        }

        // Calculate price_eur = round(price_per_unit * fx_rate_to_eur, 8)
        $price_eur = $this->multiply_decimals($input['price_per_unit'], $fx_rate_to_eur);
        $price_eur = $this->round_decimal($price_eur, 8);

        // Calculate total_value_eur = round(quantity * price_eur, 2)
        $total_value_eur = $this->multiply_decimals($sell_qty, $price_eur);
        $total_value_eur = $this->round_decimal($total_value_eur, 2);

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
            'price_eur' => $price_eur,
            'trade_currency' => $trade_currency,
            'fee_amount' => $input['fee_amount'] ?? null,
            'fee_currency' => isset($input['fee_currency']) ? strtoupper(trim($input['fee_currency'])) : null,
            'fx_rate_to_eur' => $fx_rate_to_eur,
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

            // Create allocation (tax fields computed at read-time)
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

        // Link documents if provided
        if (isset($input['document_ids']) && is_array($input['document_ids'])) {
            $this->link_documents($user_id, $sell_trade_id, $input['document_ids']);
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

    public function get_trade(int $user_id, int $trade_id): Trade
    {
        $trade = $this->trade_repo->find_by_id($user_id, $trade_id);
        if ($trade === null) {
            throw new NotFoundException('Trade not found');
        }
        return $trade;
    }

    /**
     * Get tax totals for a SELL trade by computing tax from allocations at read-time.
     * Joins with trade_lot (buy_date) and trade (sell_date) to compute tax per allocation.
     * Returns array with totals or null if trade is not a SELL or has no allocations.
     * 
     * @return array|null {
     *   'total_buy_cost_eur': string,
     *   'total_sell_proceeds_eur': string,
     *   'total_gain_eur': string,
     *   'total_normirani_stroski_eur': string,
     *   'total_tax_base_eur': string,
     *   'total_tax_eur': string,
     *   'tax_rate_percent': string|null (or 'mixed' if multiple rates),
     *   'tax_rate_min': string|null,
     *   'tax_rate_max': string|null
     * }
     */
    public function get_sell_tax_totals(int $user_id, int $sell_trade_id): ?array
    {
        $trade = $this->trade_repo->find_by_id($user_id, $sell_trade_id);
        if ($trade === null || $trade->trade_type !== 'SELL') {
            return null;
        }

        $allocations_data = $this->allocation_repo->list_by_sell_trade($user_id, $sell_trade_id);
        if (empty($allocations_data)) {
            return null;
        }

        // Extract sell_date and year from trade
        $sell_date = new \DateTimeImmutable($trade->trade_date);
        $sell_year = (int) $sell_date->format('Y');

        $total_buy_cost = '0.00';
        $total_sell_proceeds = '0.00';
        $total_gain = '0.00';
        $total_normirani_stroski = '0.00';
        $total_tax_base = '0.00';
        $total_tax = '0.00';
        
        $tax_rates = [];
        
        foreach ($allocations_data as $alloc_data) {
            $alloc = $alloc_data['allocation'];
            $buy_date = new \DateTimeImmutable($alloc_data['lot_opened_date']);
            
            // Sum basic fields
            $total_buy_cost = $this->add_decimals($total_buy_cost, $alloc->cost_basis_eur);
            $total_sell_proceeds = $this->add_decimals($total_sell_proceeds, $alloc->proceeds_eur);
            $total_gain = $this->add_decimals($total_gain, $alloc->realized_pnl_eur);
            
            // Compute tax fields at read-time
            // gain = proceeds_eur - cost_basis_eur (already computed as realized_pnl_eur)
            $gain_eur = $alloc->realized_pnl_eur;
            
            // norm = min(0.01*cost_basis_eur + 0.01*proceeds_eur, max(0, gain))
            $normirani_stroski_eur = $this->compute_normirani_stroski(
                $alloc->cost_basis_eur, 
                $alloc->proceeds_eur, 
                $gain_eur
            );
            
            // tax_base = max(0, gain - norm)
            $tax_base_eur = $this->subtract_decimals($gain_eur, $normirani_stroski_eur);
            if ($this->compare_decimals($tax_base_eur, '0') < 0) {
                $tax_base_eur = '0.00';
            } else {
                $tax_base_eur = $this->round_decimal($tax_base_eur, 2);
            }
            
            // holding_years = full years between buy_date and sell_date
            $holding_years = $buy_date->diff($sell_date)->y;
            
            // rate = get_slovenia_tax_rate(sell_year, holding_years)
            $tax_rate_percent = $this->get_slovenia_tax_rate($sell_year, $holding_years);
            
            // tax = tax_base * rate
            $tax_eur = $this->multiply_decimals($tax_base_eur, (string)($tax_rate_percent / 100));
            $tax_eur = $this->round_decimal($tax_eur, 2);
            
            // Sum computed tax fields
            $total_normirani_stroski = $this->add_decimals($total_normirani_stroski, $normirani_stroski_eur);
            $total_tax_base = $this->add_decimals($total_tax_base, $tax_base_eur);
            $total_tax = $this->add_decimals($total_tax, $tax_eur);
            $tax_rates[] = (string)$tax_rate_percent;
        }
        
        // Round totals
        $total_buy_cost = $this->round_decimal($total_buy_cost, 2);
        $total_sell_proceeds = $this->round_decimal($total_sell_proceeds, 2);
        $total_gain = $this->round_decimal($total_gain, 2);
        $total_normirani_stroski = $this->round_decimal($total_normirani_stroski, 2);
        $total_tax_base = $this->round_decimal($total_tax_base, 2);
        $total_tax = $this->round_decimal($total_tax, 2);
        
        // Determine tax rate display
        $tax_rate_percent_display = null;
        $tax_rate_min = null;
        $tax_rate_max = null;
        
        if (!empty($tax_rates)) {
            $unique_rates = array_unique($tax_rates);
            if (count($unique_rates) === 1) {
                $tax_rate_percent_display = (string)reset($unique_rates);
            } else {
                $tax_rate_percent_display = 'mixed';
                $tax_rate_min = (string)min($unique_rates);
                $tax_rate_max = (string)max($unique_rates);
            }
        }
        
        return [
            'total_buy_cost_eur' => $total_buy_cost,
            'total_sell_proceeds_eur' => $total_sell_proceeds,
            'total_gain_eur' => $total_gain,
            'total_normirani_stroski_eur' => $total_normirani_stroski,
            'total_tax_base_eur' => $total_tax_base,
            'total_tax_eur' => $total_tax,
            'tax_rate_percent' => $tax_rate_percent_display,
            'tax_rate_min' => $tax_rate_min,
            'tax_rate_max' => $tax_rate_max,
        ];
    }

    /**
     * Get available quantity for an instrument, filtered by broker_account_id (if provided).
     * Returns quantity as string with 6 decimal precision.
     */
    public function get_available_quantity(int $user_id, int $instrument_id, ?int $broker_account_id = null, ?string $trade_date = null): string
    {
        $qty = $this->lot_repo->get_available_quantity($user_id, $instrument_id, $broker_account_id, $trade_date);
        // Ensure 6 decimal precision
        return $this->round_decimal($qty, 6);
    }

    /**
     * Get list of instruments with availability for sell form.
     * Returns array of ['instrument_id' => int, 'label' => string, 'available_qty' => string]
     */
    public function get_instruments_for_sell(int $user_id, ?int $broker_account_id = null, ?string $trade_date = null, bool $include_zero = false): array
    {
        $instruments_with_qty = $this->lot_repo->get_instruments_with_availability($user_id, $broker_account_id, $trade_date, $include_zero);

        $result = [];
        foreach ($instruments_with_qty as $item) {
            $instrument = $this->instrument_repo->find_by_id($item['instrument_id']);
            if ($instrument === null) {
                continue; // Skip if instrument not found
            }

            $label = $instrument->ticker 
                ? $instrument->ticker . ' - ' . $instrument->name 
                : $instrument->name;

            $result[] = [
                'instrument_id' => $item['instrument_id'],
                'label' => $label,
                'available_qty' => $this->round_decimal($item['available_qty'], 6),
            ];
        }

        return $result;
    }

    public function update_buy(int $user_id, int $trade_id, array $input): void
    {
        $errors = $this->validate($input);

        if (!empty($errors)) {
            throw new ValidationException('Validation failed', $errors);
        }

        $existing_trade = $this->trade_repo->find_by_id($user_id, $trade_id);
        if ($existing_trade === null || $existing_trade->trade_type !== 'BUY') {
            throw new NotFoundException('Buy trade not found');
        }

        // Convert broker_fx_rate to fx_rate_to_eur
        // Store with high precision (12 decimals) for accuracy
        $trade_currency = strtoupper(trim($input['trade_currency']));
        $fx_rate_to_eur = '1.000000000000';
        if ($trade_currency !== 'EUR' && isset($input['broker_fx_rate']) && $input['broker_fx_rate'] !== '') {
            $fx_rate_to_eur = $this->divide_decimals('1', $input['broker_fx_rate']);
            $fx_rate_to_eur = $this->round_decimal($fx_rate_to_eur, 12);
        }

        // Calculate price_eur = round(price_per_unit * fx_rate_to_eur, 8)
        $price_eur = $this->multiply_decimals($input['price_per_unit'], $fx_rate_to_eur);
        $price_eur = $this->round_decimal($price_eur, 8);

        // Calculate total_value_eur = round(quantity * price_eur, 2)
        $total_value_eur = $this->multiply_decimals($input['quantity'], $price_eur);
        $total_value_eur = $this->round_decimal($total_value_eur, 2);

        $fee_eur = isset($input['fee_eur']) && $input['fee_eur'] !== '' 
            ? $this->round_decimal($input['fee_eur'], 2)
            : '0.00';

        // Update trade
        $trade_data = [
            'broker_account_id' => $input['broker_account_id'] ?? null,
            'instrument_id' => (int) $input['instrument_id'],
            'trade_date' => $input['trade_date'],
            'quantity' => $this->round_decimal($input['quantity'], 6),
            'price_per_unit' => $this->round_decimal($input['price_per_unit'], 8),
            'price_eur' => $price_eur,
            'trade_currency' => $trade_currency,
            'fee_amount' => $input['fee_amount'] ?? null,
            'fee_currency' => isset($input['fee_currency']) ? strtoupper(trim($input['fee_currency'])) : null,
            'fx_rate_to_eur' => $fx_rate_to_eur,
            'total_value_eur' => $total_value_eur,
            'fee_eur' => $fee_eur,
            'notes' => $input['notes'] ?? null,
        ];

        $this->trade_repo->update_trade($user_id, $trade_id, $trade_data);

        // Update lot: cost_basis includes buy fee
        $lot = $this->lot_repo->find_by_buy_trade_id($user_id, $trade_id);
        if ($lot !== null) {
            $cost_basis_eur = $this->add_decimals($total_value_eur, $fee_eur);
            $new_quantity = $this->round_decimal($input['quantity'], 6);
            
            // Calculate new quantity_remaining based on ratio if quantity changed
            $qty_ratio = $this->divide_decimals($new_quantity, $lot->quantity_opened);
            $new_quantity_remaining = $this->multiply_decimals($lot->quantity_remaining, $qty_ratio);
            $new_quantity_remaining = $this->round_decimal($new_quantity_remaining, 6);

            $lot_data = [
                'instrument_id' => (int) $input['instrument_id'],
                'opened_date' => $input['trade_date'],
                'quantity_opened' => $new_quantity,
                'quantity_remaining' => $new_quantity_remaining,
                'cost_basis_eur' => $cost_basis_eur,
            ];

            $this->lot_repo->update_lot($user_id, $lot->id, $lot_data);
        }

        // Update document links if provided
        if (isset($input['document_ids']) && is_array($input['document_ids'])) {
            $this->replace_document_links($user_id, $trade_id, $input['document_ids']);
        }
    }

    public function update_sell(int $user_id, int $trade_id, array $input): void
    {
        $errors = $this->validate($input);
        $instrument_id = (int) $input['instrument_id'];

        $existing_trade = $this->trade_repo->find_by_id($user_id, $trade_id);
        if ($existing_trade === null || $existing_trade->trade_type !== 'SELL') {
            throw new NotFoundException('Sell trade not found');
        }

        // Get existing allocations
        $existing_allocations = $this->allocation_repo->list_by_sell_trade($user_id, $trade_id);
        
        // Check if quantity/price changed - if so, need to recalculate allocations
        // Convert broker_fx_rate to fx_rate_to_eur
        // Store with high precision (12 decimals) for accuracy
        $trade_currency = strtoupper(trim($input['trade_currency']));
        $fx_rate_to_eur = '1.000000000000';
        if ($trade_currency !== 'EUR' && isset($input['broker_fx_rate']) && $input['broker_fx_rate'] !== '') {
            $fx_rate_to_eur = $this->divide_decimals('1', $input['broker_fx_rate']);
            $fx_rate_to_eur = $this->round_decimal($fx_rate_to_eur, 12);
        }

        $new_quantity = $this->round_decimal($input['quantity'], 6);
        $quantity_changed = $this->compare_decimals($new_quantity, $existing_trade->quantity) !== 0;
        
        $new_price_eur = $this->multiply_decimals($input['price_per_unit'], $fx_rate_to_eur);
        $new_price_eur = $this->round_decimal($new_price_eur, 8);
        $price_changed = $this->compare_decimals($new_price_eur, $existing_trade->price_eur) !== 0;

        if ($quantity_changed) {
            // Check user has enough open quantity (accounting for what we're about to restore)
            $total_open_qty = $this->lot_repo->get_total_open_quantity($user_id, $instrument_id);
            // Add back the quantity from existing allocations
            foreach ($existing_allocations as $alloc_data) {
                $total_open_qty = $this->add_decimals($total_open_qty, $alloc_data['allocation']->quantity_consumed);
            }
            
            if ($this->compare_decimals($total_open_qty, $new_quantity) < 0) {
                $errors['quantity'] = 'Insufficient quantity. Available: ' . $total_open_qty;
            }
        }

        if (!empty($errors)) {
            throw new ValidationException('Validation failed', $errors);
        }

        // Calculate price_eur and total_value_eur (fx_rate_to_eur already converted above)
        $price_eur = $new_price_eur;
        $total_value_eur = $this->multiply_decimals($new_quantity, $price_eur);
        $total_value_eur = $this->round_decimal($total_value_eur, 2);

        $fee_eur = isset($input['fee_eur']) && $input['fee_eur'] !== '' 
            ? $this->round_decimal($input['fee_eur'], 2)
            : '0.00';

        // If quantity or price changed, need to reverse and recreate allocations
        if ($quantity_changed || $price_changed) {
            // Reverse allocations: restore lot quantities
            foreach ($existing_allocations as $alloc_data) {
                $alloc = $alloc_data['allocation'];
                $lot = $this->lot_repo->find_by_id($user_id, $alloc->trade_lot_id);
                if ($lot !== null) {
                    $restored_qty = $this->add_decimals($lot->quantity_remaining, $alloc->quantity_consumed);
                    $restored_qty = $this->round_decimal($restored_qty, 6);
                    $this->lot_repo->update_quantity_remaining($user_id, $lot->id, $restored_qty);
                }
            }

            // Delete existing allocations
            $this->allocation_repo->delete_by_sell_trade($user_id, $trade_id);

            // Recreate allocations with new values (tax fields computed at read-time)
            $sell_proceeds_net_eur = $this->subtract_decimals($total_value_eur, $fee_eur);
            $lots = $this->lot_repo->list_open_lots_fifo($user_id, $instrument_id);
            
            $remaining_to_consume = $new_quantity;
            foreach ($lots as $lot) {
                if ($this->compare_decimals($remaining_to_consume, '0') <= 0) {
                    break;
                }

                $qty_to_consume = $this->min_decimal($remaining_to_consume, $lot->quantity_remaining);
                $proceeds_part_eur = $this->multiply_decimals(
                    $sell_proceeds_net_eur,
                    $this->divide_decimals($qty_to_consume, $new_quantity)
                );
                $cost_basis_part_eur = $this->multiply_decimals(
                    $lot->cost_basis_eur,
                    $this->divide_decimals($qty_to_consume, $lot->quantity_opened)
                );

                $proceeds_part_eur = $this->round_decimal($proceeds_part_eur, 2);
                $cost_basis_part_eur = $this->round_decimal($cost_basis_part_eur, 2);
                $realized_pnl_eur = $this->subtract_decimals($proceeds_part_eur, $cost_basis_part_eur);
                $realized_pnl_eur = $this->round_decimal($realized_pnl_eur, 2);

                // Create allocation (tax fields computed at read-time)
                $allocation_data = [
                    'sell_trade_id' => $trade_id,
                    'trade_lot_id' => $lot->id,
                    'quantity_consumed' => $this->round_decimal($qty_to_consume, 6),
                    'proceeds_eur' => $proceeds_part_eur,
                    'cost_basis_eur' => $cost_basis_part_eur,
                    'realized_pnl_eur' => $realized_pnl_eur,
                ];

                $this->allocation_repo->create_allocation($user_id, $allocation_data);

                $new_remaining = $this->subtract_decimals($lot->quantity_remaining, $qty_to_consume);
                $new_remaining = $this->round_decimal($new_remaining, 6);
                $this->lot_repo->update_quantity_remaining($user_id, $lot->id, $new_remaining);

                $remaining_to_consume = $this->subtract_decimals($remaining_to_consume, $qty_to_consume);
            }
        }

        // Update trade
        $trade_data = [
            'broker_account_id' => $input['broker_account_id'] ?? null,
            'instrument_id' => $instrument_id,
            'trade_date' => $input['trade_date'],
            'quantity' => $new_quantity,
            'price_per_unit' => $this->round_decimal($input['price_per_unit'], 8),
            'price_eur' => $price_eur,
            'trade_currency' => $trade_currency,
            'fee_amount' => $input['fee_amount'] ?? null,
            'fee_currency' => isset($input['fee_currency']) ? strtoupper(trim($input['fee_currency'])) : null,
            'fx_rate_to_eur' => $fx_rate_to_eur,
            'total_value_eur' => $total_value_eur,
            'fee_eur' => $fee_eur,
            'notes' => $input['notes'] ?? null,
        ];

        $this->trade_repo->update_trade($user_id, $trade_id, $trade_data);

        // Update document links if provided
        if (isset($input['document_ids']) && is_array($input['document_ids'])) {
            $this->replace_document_links($user_id, $trade_id, $input['document_ids']);
        }
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

        // price_per_unit >= 0
        if (!isset($input['price_per_unit']) || $input['price_per_unit'] === '') {
            $errors['price_per_unit'] = 'Price per unit is required';
        } elseif ($this->compare_decimals($input['price_per_unit'], '0') < 0) {
            $errors['price_per_unit'] = 'Price per unit cannot be negative';
        }

        // trade_currency required (3 letters)
        if (!isset($input['trade_currency']) || trim($input['trade_currency']) === '') {
            $errors['trade_currency'] = 'Trade currency is required';
        } elseif (strlen(trim($input['trade_currency'])) !== 3) {
            $errors['trade_currency'] = 'Trade currency must be 3 characters';
        }

        // broker_fx_rate > 0 (only for non-EUR trades)
        $trade_currency = isset($input['trade_currency']) ? strtoupper(trim($input['trade_currency'])) : '';
        if ($trade_currency !== 'EUR') {
            if (!isset($input['broker_fx_rate']) || $input['broker_fx_rate'] === '') {
                $errors['broker_fx_rate'] = 'FX rate (EUR → ' . $trade_currency . ') is required';
            } elseif ($this->compare_decimals($input['broker_fx_rate'], '0') <= 0) {
                $errors['broker_fx_rate'] = 'FX rate must be greater than 0';
            }
        }

        // fee_eur >= 0 (optional)
        if (isset($input['fee_eur']) && $input['fee_eur'] !== '') {
            if ($this->compare_decimals($input['fee_eur'], '0') < 0) {
                $errors['fee_eur'] = 'Fee cannot be negative';
            }
        }

        return $errors;
    }

    /**
     * Get Slovenian capital gains tax rate based on sell year and holding period.
     * 
     * Rate mapping:
     * A) sell_year <= 2019: <5:25; >=5:15; >=10:10; >=15:5; >=20:0
     * B) sell_year 2020-2021: <5:27.5; >=5:20; >=10:15; >=15:10; >=20:0
     * C) sell_year >= 2022: <5:25; >=5:20; >=10:15; >=15:0; >=20:0
     * 
     * @param int $sell_year Year when the sell occurred
     * @param int $holding_years Full years between buy and sell dates
     * @return float Tax rate as percentage (e.g., 25.0 for 25%)
     */
    public function get_slovenia_tax_rate(int $sell_year, int $holding_years): float
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

    /**
     * Compute normirani stroški (standardized costs) for Slovenian tax.
     * Formula: norm = min(0.01 * buy_cost + 0.01 * sell_proceeds, max(0, gain))
     * 
     * @param string $buy_cost_eur Buy cost in EUR
     * @param string $sell_proceeds_eur Sell proceeds in EUR
     * @param string $gain_eur Gain (sell_proceeds - buy_cost) in EUR
     * @return string Normirani stroški amount in EUR
     */
    public function compute_normirani_stroski(string $buy_cost_eur, string $sell_proceeds_eur, string $gain_eur): string
    {
        // norm_raw = 0.01 * buy_cost + 0.01 * sell_proceeds
        $norm_raw = $this->multiply_decimals('0.01', $buy_cost_eur);
        $norm_raw = $this->add_decimals($norm_raw, $this->multiply_decimals('0.01', $sell_proceeds_eur));
        
        // norm = min(norm_raw, max(0, gain))
        $gain_non_neg = $this->compare_decimals($gain_eur, '0') > 0 ? $gain_eur : '0.00';
        $norm = $this->compare_decimals($norm_raw, $gain_non_neg) <= 0 ? $norm_raw : $gain_non_neg;
        
        return $this->round_decimal($norm, 2);
    }

    // Decimal-safe math functions
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

    /**
     * Link documents to a trade (with ownership validation)
     */
    private function link_documents(int $user_id, int $trade_id, array $document_ids): void
    {
        if (empty($document_ids)) {
            return;
        }

        $document_repo = new DocumentRepository();

        // Validate all documents belong to user
        foreach ($document_ids as $doc_id) {
            $doc = $document_repo->find_by_id($user_id, (int) $doc_id);
            if ($doc === null) {
                throw new \Exception('Document not found or does not belong to you');
            }
        }

        // Link all documents
        foreach ($document_ids as $doc_id) {
            $this->trade_document_repo->link($trade_id, (int) $doc_id);
        }
    }

    /**
     * Replace all document links for a trade (with ownership validation)
     */
    private function replace_document_links(int $user_id, int $trade_id, array $document_ids): void
    {
        $document_repo = new DocumentRepository();

        // Validate all documents belong to user
        foreach ($document_ids as $doc_id) {
            $doc = $document_repo->find_by_id($user_id, (int) $doc_id);
            if ($doc === null) {
                throw new \Exception('Document not found or does not belong to you');
            }
        }

        // Replace all links
        $this->trade_document_repo->replace_links($trade_id, array_map('intval', $document_ids));
    }

    /**
     * Get all document IDs linked to a trade
     */
    public function get_linked_document_ids(int $trade_id): array
    {
        return $this->trade_document_repo->get_document_ids($trade_id);
    }
}

