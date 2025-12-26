<?php

declare(strict_types=1);

/**
 * Corporate Action Service
 * 
 * Handles corporate actions like stock splits that adjust FIFO lots without creating trades.
 * 
 * Why splits update lots directly, not trades:
 * - Stock splits are not buy/sell transactions; they're structural changes to existing holdings
 * - Tax authorities require cost basis to remain unchanged; only quantities adjust
 * - Historical trade records must remain accurate (you didn't "buy" more shares on split date)
 * - FIFO lot tracking must reflect the new quantities for future sell calculations
 * - Updating lots directly maintains audit trail: original buy trade + split adjustment = current lot state
 */
class CorporateActionService
{
    private TradeLotRepository $lot_repo;
    private InstrumentRepository $instrument_repo;

    public function __construct()
    {
        $this->lot_repo = new TradeLotRepository();
        $this->instrument_repo = new InstrumentRepository();
    }

    public function apply_stock_split(int $user_id, int $instrument_id, string $split_date, int $ratio_from, int $ratio_to): void
    {
        $errors = $this->validate($instrument_id, $split_date, $ratio_from, $ratio_to);

        if (!empty($errors)) {
            throw new ValidationException('Validation failed', $errors);
        }

        // Calculate multiplier
        $multiplier = bcdiv((string) $ratio_to, (string) $ratio_from, 8);

        // Get all open lots that need adjustment
        $lots = $this->lot_repo->list_open_lots_for_split($user_id, $instrument_id, $split_date);

        if (empty($lots)) {
            throw new ValidationException('No open lots found for this instrument on or before the split date');
        }

        // Use transaction to ensure all-or-nothing update
        $db = Database::get_connection();
        $db->beginTransaction();

        try {
            foreach ($lots as $lot) {
                // Calculate new quantities: multiply by ratio_to/ratio_from
                $new_qty_opened = bcmul($lot->quantity_opened, $multiplier, 8);
                $new_qty_remaining = bcmul($lot->quantity_remaining, $multiplier, 8);

                // Round to 6 decimals (DECIMAL(18,6) precision)
                $new_qty_opened = $this->round_decimal($new_qty_opened, 6);
                $new_qty_remaining = $this->round_decimal($new_qty_remaining, 6);

                // Update lot quantities (cost_basis_eur remains unchanged)
                $this->lot_repo->update_lot_quantities(
                    $user_id,
                    $lot->id,
                    $new_qty_opened,
                    $new_qty_remaining
                );
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    private function validate(int $instrument_id, string $split_date, int $ratio_from, int $ratio_to): array
    {
        $errors = [];

        // instrument_id must exist
        $instrument = $this->instrument_repo->find_by_id($instrument_id);
        if ($instrument === null) {
            $errors['instrument_id'] = 'Instrument not found';
        }

        // split_date must be valid date (YYYY-MM-DD)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $split_date)) {
            $errors['split_date'] = 'Invalid date format';
        }

        // ratio_from > 0
        if ($ratio_from <= 0) {
            $errors['ratio_from'] = 'Ratio from must be greater than 0';
        }

        // ratio_to > 0
        if ($ratio_to <= 0) {
            $errors['ratio_to'] = 'Ratio to must be greater than 0';
        }

        // multiplier > 0 (implicitly true if both ratios > 0, but check anyway)
        if ($ratio_from > 0 && $ratio_to > 0) {
            $multiplier = bcdiv((string) $ratio_to, (string) $ratio_from, 8);
            if (bccomp($multiplier, '0', 8) <= 0) {
                $errors['ratio_to'] = 'Invalid ratio combination';
            }
        }

        return $errors;
    }

    private function round_decimal(string $value, int $precision): string
    {
        return number_format((float) $value, $precision, '.', '');
    }
}

