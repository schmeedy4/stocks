<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Dividend;
use App\Repositories\DividendRepository;
use RuntimeException;

final class DividendService
{
    public function __construct(private DividendRepository $dividendRepository)
    {
    }

    public function addDividend(Dividend $dividend, string $payerCountryCode): int
    {
        $this->validate($dividend, $payerCountryCode);
        return $this->dividendRepository->create($dividend);
    }

    public function updateDividend(Dividend $dividend, string $payerCountryCode): void
    {
        $this->validate($dividend, $payerCountryCode);
        $this->dividendRepository->update($dividend);
    }

    /** @return Dividend[] */
    public function getDividendsForYear(int $userId, int $year): array
    {
        return $this->dividendRepository->findByUserAndYear($userId, $year);
    }

    private function validate(Dividend $dividend, string $payerCountryCode): void
    {
        if ($dividend->grossAmountEur <= 0) {
            throw new RuntimeException('Gross amount must be positive.');
        }

        if ($dividend->foreignTaxEur !== null && $dividend->foreignTaxEur < 0) {
            throw new RuntimeException('Foreign tax cannot be negative.');
        }

        if ($dividend->foreignTaxEur !== null && $dividend->foreignTaxEur > $dividend->grossAmountEur) {
            throw new RuntimeException('Foreign tax cannot exceed gross amount.');
        }

        if (strtoupper($payerCountryCode) === 'SI' && $dividend->foreignTaxEur !== null) {
            throw new RuntimeException('Foreign tax must be null when payer country is SI.');
        }
    }
}
