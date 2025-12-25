<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;

final class Trade
{
    public function __construct(
        public ?int $id,
        public int $userId,
        public int $instrumentId,
        public string $tradeType,
        public DateTimeImmutable $tradeDate,
        public float $quantity,
        public float $pricePerUnit,
        public string $tradeCurrency,
        public float $fxRateToEur,
        public float $totalValueEur,
        public float $feeEur,
        public ?int $brokerAccountId = null,
        public ?float $feeAmount = null,
        public ?string $feeCurrency = null,
        public ?string $notes = null,
        public bool $isVoided = false,
        public ?string $voidReason = null,
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null,
        public ?int $createdBy = null,
        public ?int $updatedBy = null,
    ) {
    }
}
