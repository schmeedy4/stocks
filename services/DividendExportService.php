<?php

declare(strict_types=1);

namespace App\Services;

use App\Exports\DohDivCsvExporter;
use App\Repositories\AppSettingsRepository;
use App\Repositories\DividendPayerRepository;
use App\Repositories\DividendRepository;
use RuntimeException;

final class DividendExportService
{
    public function __construct(
        private DividendRepository $dividendRepository,
        private DividendPayerRepository $payerRepository,
        private AppSettingsRepository $settingsRepository,
        private DohDivCsvExporter $exporter,
    ) {
    }

    public function exportForYear(int $userId, int $year): string
    {
        $settings = $this->settingsRepository->findByUserId($userId);
        if ($settings === null || empty($settings->taxPayerId)) {
            throw new RuntimeException('Tax payer settings are required for export.');
        }

        $dividends = $this->dividendRepository->findByUserAndYear($userId, $year);

        $rows = [];
        $sequencePerPayerDate = [];
        foreach ($dividends as $dividend) {
            $payer = $this->payerRepository->findById($userId, $dividend->dividendPayerId);
            if ($payer === null) {
                throw new RuntimeException('Dividend payer not found for dividend ID ' . ($dividend->id ?? '')); 
            }

            $key = $payer->id . '|' . $dividend->receivedDate->format('Y-m-d');
            if (!isset($sequencePerPayerDate[$key])) {
                $sequencePerPayerDate[$key] = 1;
            }

            $payerIdent = $dividend->payerIdentForExport;
            if ($payerIdent === null || $payerIdent === '') {
                $payerIdent = (string) $sequencePerPayerDate[$key];
            }
            $sequencePerPayerDate[$key]++;

            $rows[] = [
                'received_date' => $dividend->receivedDate->format('d. m. Y'),
                'payer_name' => $payer->payerName,
                'payer_address' => $payer->payerAddress,
                'payer_country_code' => $payer->payerCountryCode,
                'payer_ident_for_export' => $payerIdent,
                'dividend_type_code' => $dividend->dividendTypeCode,
                'source_country_code' => $dividend->sourceCountryCode,
                'gross_amount_eur' => number_format($dividend->grossAmountEur, 2, '.', ''),
                'foreign_tax_eur' => $dividend->foreignTaxEur !== null ? number_format($dividend->foreignTaxEur, 2, '.', '') : '',
                'notes' => $dividend->notes ?? '',
            ];
        }

        return $this->exporter->export($settings, $rows);
    }
}
