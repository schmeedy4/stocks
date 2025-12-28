<?php

declare(strict_types=1);

class TradeLotRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::get_connection();
    }

    public function list_open_lots_fifo(int $user_id, int $instrument_id): array
    {
        $stmt = $this->db->prepare('
            SELECT id, user_id, buy_trade_id, instrument_id, opened_date,
                   quantity_opened, quantity_remaining, cost_basis_eur
            FROM trade_lot
            WHERE user_id = :user_id AND instrument_id = :instrument_id AND quantity_remaining > 0
            ORDER BY opened_date ASC, id ASC
        ');
        $stmt->execute(['user_id' => $user_id, 'instrument_id' => $instrument_id]);

        $lots = [];
        foreach ($stmt->fetchAll() as $row) {
            $lots[] = new TradeLot(
                (int) $row['id'],
                (int) $row['user_id'],
                (int) $row['buy_trade_id'],
                (int) $row['instrument_id'],
                $row['opened_date'],
                $row['quantity_opened'],
                $row['quantity_remaining'],
                $row['cost_basis_eur']
            );
        }

        return $lots;
    }

    public function get_total_open_quantity(int $user_id, int $instrument_id): string
    {
        $stmt = $this->db->prepare('
            SELECT COALESCE(SUM(quantity_remaining), 0) as total
            FROM trade_lot
            WHERE user_id = :user_id AND instrument_id = :instrument_id
        ');
        $stmt->execute(['user_id' => $user_id, 'instrument_id' => $instrument_id]);
        $row = $stmt->fetch();
        return $row['total'] ?? '0';
    }

    /**
     * Get available quantity for an instrument, filtered by broker_account_id (if provided).
     * Only counts lots opened on or before trade_date (if provided).
     * If broker_account_id is null, counts all lots (broker-independent).
     */
    public function get_available_quantity(int $user_id, int $instrument_id, ?int $broker_account_id = null, ?string $trade_date = null): string
    {
        $sql = '
            SELECT COALESCE(SUM(tl.quantity_remaining), 0) as total
            FROM trade_lot tl
            INNER JOIN trade t ON tl.buy_trade_id = t.id
            WHERE tl.user_id = :user_id 
              AND tl.instrument_id = :instrument_id
              AND t.trade_type = \'BUY\'
        ';

        $params = [
            'user_id' => $user_id,
            'instrument_id' => $instrument_id,
        ];

        // If broker_account_id is specified, filter by it (including NULL broker_account_id if provided value is special)
        // If broker_account_id is null, show all lots regardless of broker
        if ($broker_account_id !== null) {
            $sql .= ' AND t.broker_account_id = :broker_account_id';
            $params['broker_account_id'] = $broker_account_id;
        }
        // else: no filter on broker_account_id, show all

        if ($trade_date !== null) {
            $sql .= ' AND tl.opened_date <= :trade_date';
            $params['trade_date'] = $trade_date;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row['total'] ?? '0';
    }

    /**
     * Get list of instruments with available quantities, filtered by broker_account_id (if provided).
     * Returns array of ['instrument_id' => int, 'available_qty' => string]
     * If broker_account_id is null, includes all instruments regardless of broker.
     */
    public function get_instruments_with_availability(int $user_id, ?int $broker_account_id = null, ?string $trade_date = null, bool $include_zero = false): array
    {
        $sql = '
            SELECT 
                tl.instrument_id,
                COALESCE(SUM(tl.quantity_remaining), 0) as available_qty
            FROM trade_lot tl
            INNER JOIN trade t ON tl.buy_trade_id = t.id
            WHERE tl.user_id = :user_id 
              AND t.trade_type = \'BUY\'
        ';

        $params = ['user_id' => $user_id];

        // If broker_account_id is specified, filter by it
        // If broker_account_id is null, show all lots regardless of broker
        if ($broker_account_id !== null) {
            $sql .= ' AND t.broker_account_id = :broker_account_id';
            $params['broker_account_id'] = $broker_account_id;
        }
        // else: no filter on broker_account_id

        if ($trade_date !== null) {
            $sql .= ' AND tl.opened_date <= :trade_date';
            $params['trade_date'] = $trade_date;
        }

        $sql .= ' GROUP BY tl.instrument_id';

        if (!$include_zero) {
            $sql .= ' HAVING available_qty > 0';
        }

        $sql .= ' ORDER BY tl.instrument_id';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[] = [
                'instrument_id' => (int) $row['instrument_id'],
                'available_qty' => $row['available_qty'],
            ];
        }

        return $result;
    }

    public function create_lot(int $user_id, array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO trade_lot (
                user_id, buy_trade_id, instrument_id, opened_date,
                quantity_opened, quantity_remaining, cost_basis_eur
            )
            VALUES (
                :user_id, :buy_trade_id, :instrument_id, :opened_date,
                :quantity_opened, :quantity_remaining, :cost_basis_eur
            )
        ');
        $stmt->execute([
            'user_id' => $user_id,
            'buy_trade_id' => $data['buy_trade_id'],
            'instrument_id' => $data['instrument_id'],
            'opened_date' => $data['opened_date'],
            'quantity_opened' => $data['quantity_opened'],
            'quantity_remaining' => $data['quantity_remaining'],
            'cost_basis_eur' => $data['cost_basis_eur'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update_quantity_remaining(int $user_id, int $lot_id, string $new_qty): void
    {
        $stmt = $this->db->prepare('
            UPDATE trade_lot
            SET quantity_remaining = :new_qty
            WHERE user_id = :user_id AND id = :lot_id
        ');
        $stmt->execute([
            'user_id' => $user_id,
            'lot_id' => $lot_id,
            'new_qty' => $new_qty,
        ]);
    }

    public function find_by_id(int $user_id, int $lot_id): ?TradeLot
    {
        $stmt = $this->db->prepare('
            SELECT id, user_id, buy_trade_id, instrument_id, opened_date,
                   quantity_opened, quantity_remaining, cost_basis_eur
            FROM trade_lot
            WHERE user_id = :user_id AND id = :lot_id
        ');
        $stmt->execute(['user_id' => $user_id, 'lot_id' => $lot_id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return new TradeLot(
            (int) $row['id'],
            (int) $row['user_id'],
            (int) $row['buy_trade_id'],
            (int) $row['instrument_id'],
            $row['opened_date'],
            $row['quantity_opened'],
            $row['quantity_remaining'],
            $row['cost_basis_eur']
        );
    }

    public function find_by_buy_trade_id(int $user_id, int $buy_trade_id): ?TradeLot
    {
        $stmt = $this->db->prepare('
            SELECT id, user_id, buy_trade_id, instrument_id, opened_date,
                   quantity_opened, quantity_remaining, cost_basis_eur
            FROM trade_lot
            WHERE user_id = :user_id AND buy_trade_id = :buy_trade_id
        ');
        $stmt->execute(['user_id' => $user_id, 'buy_trade_id' => $buy_trade_id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return new TradeLot(
            (int) $row['id'],
            (int) $row['user_id'],
            (int) $row['buy_trade_id'],
            (int) $row['instrument_id'],
            $row['opened_date'],
            $row['quantity_opened'],
            $row['quantity_remaining'],
            $row['cost_basis_eur']
        );
    }

    public function update_lot(int $user_id, int $lot_id, array $data): void
    {
        $stmt = $this->db->prepare('
            UPDATE trade_lot
            SET instrument_id = :instrument_id,
                opened_date = :opened_date,
                quantity_opened = :quantity_opened,
                quantity_remaining = :quantity_remaining,
                cost_basis_eur = :cost_basis_eur
            WHERE user_id = :user_id AND id = :lot_id
        ');
        $stmt->execute([
            'user_id' => $user_id,
            'lot_id' => $lot_id,
            'instrument_id' => $data['instrument_id'],
            'opened_date' => $data['opened_date'],
            'quantity_opened' => $data['quantity_opened'],
            'quantity_remaining' => $data['quantity_remaining'],
            'cost_basis_eur' => $data['cost_basis_eur'],
        ]);
    }

    public function list_open_lots_for_split(int $user_id, int $instrument_id, string $split_date): array
    {
        $stmt = $this->db->prepare('
            SELECT id, user_id, buy_trade_id, instrument_id, opened_date,
                   quantity_opened, quantity_remaining, cost_basis_eur
            FROM trade_lot
            WHERE user_id = :user_id 
              AND instrument_id = :instrument_id 
              AND opened_date <= :split_date
              AND quantity_remaining > 0
            ORDER BY opened_date ASC, id ASC
        ');
        $stmt->execute([
            'user_id' => $user_id,
            'instrument_id' => $instrument_id,
            'split_date' => $split_date,
        ]);

        $lots = [];
        foreach ($stmt->fetchAll() as $row) {
            $lots[] = new TradeLot(
                (int) $row['id'],
                (int) $row['user_id'],
                (int) $row['buy_trade_id'],
                (int) $row['instrument_id'],
                $row['opened_date'],
                $row['quantity_opened'],
                $row['quantity_remaining'],
                $row['cost_basis_eur']
            );
        }

        return $lots;
    }

    public function update_lot_quantities(int $user_id, int $lot_id, string $qty_opened, string $qty_remaining): void
    {
        $stmt = $this->db->prepare('
            UPDATE trade_lot
            SET quantity_opened = :qty_opened,
                quantity_remaining = :qty_remaining
            WHERE user_id = :user_id AND id = :lot_id
        ');
        $stmt->execute([
            'user_id' => $user_id,
            'lot_id' => $lot_id,
            'qty_opened' => $qty_opened,
            'qty_remaining' => $qty_remaining,
        ]);
    }

    /**
     * Get all open lots for a user, grouped by instrument_id.
     * Returns array with instrument_id as key, array of TradeLot objects as value.
     */
    public function get_all_open_lots_grouped_by_instrument(int $user_id): array
    {
        $stmt = $this->db->prepare('
            SELECT id, user_id, buy_trade_id, instrument_id, opened_date,
                   quantity_opened, quantity_remaining, cost_basis_eur
            FROM trade_lot
            WHERE user_id = :user_id AND quantity_remaining > 0
            ORDER BY instrument_id, opened_date ASC, id ASC
        ');
        $stmt->execute(['user_id' => $user_id]);

        $grouped = [];
        foreach ($stmt->fetchAll() as $row) {
            $instrument_id = (int) $row['instrument_id'];
            if (!isset($grouped[$instrument_id])) {
                $grouped[$instrument_id] = [];
            }
            $grouped[$instrument_id][] = new TradeLot(
                (int) $row['id'],
                (int) $row['user_id'],
                (int) $row['buy_trade_id'],
                (int) $row['instrument_id'],
                $row['opened_date'],
                $row['quantity_opened'],
                $row['quantity_remaining'],
                $row['cost_basis_eur']
            );
        }

        return $grouped;
    }
}

