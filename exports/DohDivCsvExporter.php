<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\AppSettings;

final class DohDivCsvExporter
{
    /**
     * @param array<int, array<string, string>> $rows
     */
    public function export(AppSettings $settings, array $rows): string
    {
        $lines = [];
        $metaHeader = ['#FormCode', 'Version', 'TaxPayerID', 'TaxPayerType', 'DocumentWorkflowID', '', '', '', '', '', ''];
        $metaValues = ['DOH-DIV', '3.9', (string) $settings->taxPayerId, $settings->taxPayerType, $settings->documentWorkflowId, '', '', '', '', '', ''];
        $lines[] = $this->toCsvLine($metaHeader);
        $lines[] = $this->toCsvLine($metaValues);

        $header = [
            'Datum prejema',
            'Naziv izplačevalca',
            'Naslov izplačevalca',
            'Država izplačevalca',
            'Identifikacijska številka izplačevalca',
            'Vrsta dohodka',
            'Država vira',
            'Bruto (EUR)',
            'Davek v tujini (EUR)',
            'Opombe',
        ];
        $lines[] = $this->toCsvLine($header);

        foreach ($rows as $row) {
            $line = [
                $row['received_date'],
                $row['payer_name'],
                $row['payer_address'],
                $row['payer_country_code'],
                $row['payer_ident_for_export'],
                $row['dividend_type_code'],
                $row['source_country_code'],
                $row['gross_amount_eur'],
                $row['foreign_tax_eur'],
                $row['notes'],
            ];
            $lines[] = $this->toCsvLine($line);
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * @param array<int, string> $values
     */
    private function toCsvLine(array $values): string
    {
        $escaped = array_map(static function (string $value): string {
            $value = str_replace('"', '""', $value);
            return str_contains($value, ';') ? '"' . $value . '"' : $value;
        }, $values);

        return implode(';', $escaped);
    }
}
