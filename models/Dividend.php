<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;

final class Dividend
{
    public function __construct(
        public ?int $id,
        public int $userId,
        public int $dividendPayerId,
        public DateTimeImmutable $receivedDate,
        public string $dividendTypeCode,
        public string $sourceCountryCode,
        public float $grossAmountEur,
        public ?float $foreignTaxEur,
        public ?int $brokerAccountId = null,
        public ?int $instrumentId = null,
        public ?DateTimeImmutable $exDate = null,
        public ?DateTimeImmutable $payDate = null,
        public ?string $originalCurrency = null,
        public ?float $grossAmountOriginal = null,
        public ?float $foreignTaxOriginal = null,
        public ?float $fxRateToEur = null,
        public ?string $payerIdentForExport = null,
        public ?string $treatyExemptionText = null,
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
