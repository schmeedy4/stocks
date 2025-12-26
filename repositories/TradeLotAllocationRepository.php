<?php

declare(strict_types=1);

class TradeLotAllocationRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::get_connection();
    }

    public function create_allocation(int $user_id, array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO trade_lot_allocation (
                user_id, sell_trade_id, trade_lot_id, quantity_consumed,
                proceeds_eur, cost_basis_eur, realized_pnl_eur
            )
            VALUES (
                :user_id, :sell_trade_id, :trade_lot_id, :quantity_consumed,
                :proceeds_eur, :cost_basis_eur, :realized_pnl_eur
            )
        ');
        $stmt->execute([
            'user_id' => $user_id,
            'sell_trade_id' => $data['sell_trade_id'],
            'trade_lot_id' => $data['trade_lot_id'],
            'quantity_consumed' => $data['quantity_consumed'],
            'proceeds_eur' => $data['proceeds_eur'],
            'cost_basis_eur' => $data['cost_basis_eur'],
            'realized_pnl_eur' => $data['realized_pnl_eur'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function list_by_sell_trade(int $user_id, int $sell_trade_id): array
    {
        $stmt = $this->db->prepare('
            SELECT 
                a.id, a.user_id, a.sell_trade_id, a.trade_lot_id, a.quantity_consumed,
                a.proceeds_eur, a.cost_basis_eur, a.realized_pnl_eur,
                l.opened_date
            FROM trade_lot_allocation a
            INNER JOIN trade_lot l ON a.trade_lot_id = l.id AND a.user_id = l.user_id
            WHERE a.user_id = :user_id AND a.sell_trade_id = :sell_trade_id
            ORDER BY a.id ASC
        ');
        $stmt->execute(['user_id' => $user_id, 'sell_trade_id' => $sell_trade_id]);

        $allocations = [];
        foreach ($stmt->fetchAll() as $row) {
            $allocations[] = [
                'allocation' => new TradeLotAllocation(
                    (int) $row['id'],
                    (int) $row['user_id'],
                    (int) $row['sell_trade_id'],
                    (int) $row['trade_lot_id'],
                    $row['quantity_consumed'],
                    $row['proceeds_eur'],
                    $row['cost_basis_eur'],
                    $row['realized_pnl_eur']
                ),
                'lot_opened_date' => $row['opened_date'],
            ];
        }

        return $allocations;
    }

    public function delete_by_sell_trade(int $user_id, int $sell_trade_id): void
    {
        $stmt = $this->db->prepare('
            DELETE FROM trade_lot_allocation
            WHERE user_id = :user_id AND sell_trade_id = :sell_trade_id
        ');
        $stmt->execute(['user_id' => $user_id, 'sell_trade_id' => $sell_trade_id]);
    }
}

