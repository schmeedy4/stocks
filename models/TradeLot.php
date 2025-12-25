<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;

final class TradeLot
{
    public function __construct(
        public ?int $id,
        public int $userId,
        public int $buyTradeId,
        public int $instrumentId,
        public DateTimeImmutable $openedDate,
        public float $quantityOpened,
        public float $quantityRemaining,
        public float $costBasisEur,
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null,
        public ?int $createdBy = null,
        public ?int $updatedBy = null,
    ) {
    }
}
