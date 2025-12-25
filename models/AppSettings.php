<?php

declare(strict_types=1);

namespace App\Models;

final class AppSettings
{
    public function __construct(
        public int $userId,
        public ?string $taxPayerId,
        public string $taxPayerType,
        public string $documentWorkflowId,
        public string $baseCurrency,
    ) {
    }
}
