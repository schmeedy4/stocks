<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\TradeLot;
use DateTimeImmutable;
use PDO;

final class TradeLotRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(TradeLot $lot): int
    {
        $sql = 'INSERT INTO trade_lot (
            user_id, buy_trade_id, instrument_id, opened_date,
            quantity_opened, quantity_remaining, cost_basis_eur,
            created_by, updated_by
        ) VALUES (
            :user_id, :buy_trade_id, :instrument_id, :opened_date,
            :quantity_opened, :quantity_remaining, :cost_basis_eur,
            :created_by, :updated_by
        )';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $lot->userId,
            'buy_trade_id' => $lot->buyTradeId,
            'instrument_id' => $lot->instrumentId,
            'opened_date' => $lot->openedDate->format('Y-m-d'),
            'quantity_opened' => $lot->quantityOpened,
            'quantity_remaining' => $lot->quantityRemaining,
            'cost_basis_eur' => $lot->costBasisEur,
            'created_by' => $lot->createdBy,
            'updated_by' => $lot->updatedBy,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @return TradeLot[] */
    public function findOpenLots(int $userId, int $instrumentId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM trade_lot WHERE user_id = :user_id AND instrument_id = :instrument_id AND quantity_remaining > 0 ORDER BY opened_date, id');
        $stmt->execute([
            'user_id' => $userId,
            'instrument_id' => $instrumentId,
        ]);

        $rows = $stmt->fetchAll();
        $lots = [];
        foreach ($rows as $row) {
            $lots[] = $this->mapRow($row);
        }

        return $lots;
    }

    public function updateRemainingQuantity(int $lotId, float $quantityRemaining): void
    {
        $stmt = $this->pdo->prepare('UPDATE trade_lot SET quantity_remaining = :quantity_remaining WHERE id = :id');
        $stmt->execute([
            'quantity_remaining' => $quantityRemaining,
            'id' => $lotId,
        ]);
    }

    private function mapRow(array $row): TradeLot
    {
        return new TradeLot(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            buyTradeId: (int) $row['buy_trade_id'],
            instrumentId: (int) $row['instrument_id'],
            openedDate: new DateTimeImmutable($row['opened_date']),
            quantityOpened: (float) $row['quantity_opened'],
            quantityRemaining: (float) $row['quantity_remaining'],
            costBasisEur: (float) $row['cost_basis_eur'],
            createdAt: isset($row['created_at']) ? new DateTimeImmutable($row['created_at']) : null,
            updatedAt: isset($row['updated_at']) ? new DateTimeImmutable($row['updated_at']) : null,
            createdBy: $row['created_by'] !== null ? (int) $row['created_by'] : null,
            updatedBy: $row['updated_by'] !== null ? (int) $row['updated_by'] : null,
        );
    }
}
