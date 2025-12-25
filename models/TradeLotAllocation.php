<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;

final class TradeLotAllocation
{
    public function __construct(
        public ?int $id,
        public int $userId,
        public int $sellTradeId,
        public int $tradeLotId,
        public float $quantityConsumed,
        public float $proceedsEur,
        public float $costBasisEur,
        public float $realizedPnlEur,
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null,
        public ?int $createdBy = null,
        public ?int $updatedBy = null,
    ) {
    }
}
