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
}

