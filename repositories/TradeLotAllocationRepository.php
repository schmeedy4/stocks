<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\TradeLotAllocation;
use DateTimeImmutable;
use PDO;

final class TradeLotAllocationRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(TradeLotAllocation $allocation): int
    {
        $sql = 'INSERT INTO trade_lot_allocation (
            user_id, sell_trade_id, trade_lot_id,
            quantity_consumed, proceeds_eur, cost_basis_eur, realized_pnl_eur,
            created_by, updated_by
        ) VALUES (
            :user_id, :sell_trade_id, :trade_lot_id,
            :quantity_consumed, :proceeds_eur, :cost_basis_eur, :realized_pnl_eur,
            :created_by, :updated_by
        )';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $allocation->userId,
            'sell_trade_id' => $allocation->sellTradeId,
            'trade_lot_id' => $allocation->tradeLotId,
            'quantity_consumed' => $allocation->quantityConsumed,
            'proceeds_eur' => $allocation->proceedsEur,
            'cost_basis_eur' => $allocation->costBasisEur,
            'realized_pnl_eur' => $allocation->realizedPnlEur,
            'created_by' => $allocation->createdBy,
            'updated_by' => $allocation->updatedBy,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
