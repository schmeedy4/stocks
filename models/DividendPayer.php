<?php

declare(strict_types=1);

namespace App\Models;

final class DividendPayer
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $payerName,
        public string $payerAddress,
        public string $payerCountryCode,
        public ?string $payerSiTaxId,
        public ?string $payerForeignTaxId,
        public ?string $defaultSourceCountryCode,
        public ?string $defaultDividendTypeCode,
        public bool $isActive,
    ) {
    }
}
