<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Dividend;
use App\Repositories\DividendPayerRepository;
use App\Services\AuthService;
use App\Services\DividendService;
use DateTimeImmutable;
use RuntimeException;

final class DividendController
{
    public function __construct(
        private DividendService $dividendService,
        private DividendPayerRepository $payerRepository,
        private AuthService $authService,
    ) {
    }

    /**
     * Example handler for POST /dividends.
     * Accepts an associative array (e.g. $_POST) and returns the created dividend ID.
     */
    public function addFromRequest(array $data): int
    {
        $userId = $this->authService->requireUserId();
        $payerId = (int) ($data['dividend_payer_id'] ?? 0);
        $payer = $this->payerRepository->findById($userId, $payerId);
        if ($payer === null) {
            throw new RuntimeException('Dividend payer not found or inactive.');
        }

        $dividend = new Dividend(
            id: null,
            userId: $userId,
            brokerAccountId: isset($data['broker_account_id']) ? (int) $data['broker_account_id'] : null,
            instrumentId: isset($data['instrument_id']) ? (int) $data['instrument_id'] : null,
            dividendPayerId: $payerId,
            receivedDate: new DateTimeImmutable((string) $data['received_date']),
            exDate: isset($data['ex_date']) ? new DateTimeImmutable((string) $data['ex_date']) : null,
            payDate: isset($data['pay_date']) ? new DateTimeImmutable((string) $data['pay_date']) : null,
            dividendTypeCode: (string) $data['dividend_type_code'],
            sourceCountryCode: (string) $data['source_country_code'],
            grossAmountEur: (float) $data['gross_amount_eur'],
            foreignTaxEur: isset($data['foreign_tax_eur']) && $data['foreign_tax_eur'] !== '' ? (float) $data['foreign_tax_eur'] : null,
            originalCurrency: $data['original_currency'] ?? null,
            grossAmountOriginal: isset($data['gross_amount_original']) ? (float) $data['gross_amount_original'] : null,
            foreignTaxOriginal: isset($data['foreign_tax_original']) ? (float) $data['foreign_tax_original'] : null,
            fxRateToEur: isset($data['fx_rate_to_eur']) ? (float) $data['fx_rate_to_eur'] : null,
            payerIdentForExport: $data['payer_ident_for_export'] ?? null,
            treatyExemptionText: $data['treaty_exemption_text'] ?? null,
            notes: $data['notes'] ?? null,
            isVoided: false,
            voidReason: null,
            createdBy: $userId,
            updatedBy: $userId,
        );

        return $this->dividendService->addDividend($dividend, $payer->payerCountryCode);
    }

    /** @return Dividend[] */
    public function listForYear(int $year): array
    {
        $userId = $this->authService->requireUserId();
        return $this->dividendService->getDividendsForYear($userId, $year);
    }

    /** @return \App\Models\DividendPayer[] */
    public function listActivePayers(): array
    {
        $userId = $this->authService->requireUserId();
        return $this->payerRepository->findActiveByUser($userId);
    }
}
