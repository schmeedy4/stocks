<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Dividend;
use DateTimeImmutable;
use PDO;

final class DividendRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(Dividend $dividend): int
    {
        $sql = 'INSERT INTO dividend (
            user_id, broker_account_id, instrument_id, dividend_payer_id,
            received_date, ex_date, pay_date,
            dividend_type_code, source_country_code,
            gross_amount_eur, foreign_tax_eur,
            original_currency, gross_amount_original, foreign_tax_original, fx_rate_to_eur,
            payer_ident_for_export, treaty_exemption_text, notes,
            is_voided, void_reason,
            created_by, updated_by
        ) VALUES (
            :user_id, :broker_account_id, :instrument_id, :dividend_payer_id,
            :received_date, :ex_date, :pay_date,
            :dividend_type_code, :source_country_code,
            :gross_amount_eur, :foreign_tax_eur,
            :original_currency, :gross_amount_original, :foreign_tax_original, :fx_rate_to_eur,
            :payer_ident_for_export, :treaty_exemption_text, :notes,
            :is_voided, :void_reason,
            :created_by, :updated_by
        )';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $dividend->userId,
            'broker_account_id' => $dividend->brokerAccountId,
            'instrument_id' => $dividend->instrumentId,
            'dividend_payer_id' => $dividend->dividendPayerId,
            'received_date' => $dividend->receivedDate->format('Y-m-d'),
            'ex_date' => $dividend->exDate?->format('Y-m-d'),
            'pay_date' => $dividend->payDate?->format('Y-m-d'),
            'dividend_type_code' => $dividend->dividendTypeCode,
            'source_country_code' => $dividend->sourceCountryCode,
            'gross_amount_eur' => $dividend->grossAmountEur,
            'foreign_tax_eur' => $dividend->foreignTaxEur,
            'original_currency' => $dividend->originalCurrency,
            'gross_amount_original' => $dividend->grossAmountOriginal,
            'foreign_tax_original' => $dividend->foreignTaxOriginal,
            'fx_rate_to_eur' => $dividend->fxRateToEur,
            'payer_ident_for_export' => $dividend->payerIdentForExport,
            'treaty_exemption_text' => $dividend->treatyExemptionText,
            'notes' => $dividend->notes,
            'is_voided' => $dividend->isVoided ? 1 : 0,
            'void_reason' => $dividend->voidReason,
            'created_by' => $dividend->createdBy,
            'updated_by' => $dividend->updatedBy,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(Dividend $dividend): void
    {
        if ($dividend->id === null) {
            throw new \InvalidArgumentException('Dividend ID is required for update.');
        }

        $sql = 'UPDATE dividend SET
            broker_account_id = :broker_account_id,
            instrument_id = :instrument_id,
            dividend_payer_id = :dividend_payer_id,
            received_date = :received_date,
            ex_date = :ex_date,
            pay_date = :pay_date,
            dividend_type_code = :dividend_type_code,
            source_country_code = :source_country_code,
            gross_amount_eur = :gross_amount_eur,
            foreign_tax_eur = :foreign_tax_eur,
            original_currency = :original_currency,
            gross_amount_original = :gross_amount_original,
            foreign_tax_original = :foreign_tax_original,
            fx_rate_to_eur = :fx_rate_to_eur,
            payer_ident_for_export = :payer_ident_for_export,
            treaty_exemption_text = :treaty_exemption_text,
            notes = :notes,
            is_voided = :is_voided,
            void_reason = :void_reason,
            updated_by = :updated_by
        WHERE id = :id AND user_id = :user_id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'broker_account_id' => $dividend->brokerAccountId,
            'instrument_id' => $dividend->instrumentId,
            'dividend_payer_id' => $dividend->dividendPayerId,
            'received_date' => $dividend->receivedDate->format('Y-m-d'),
            'ex_date' => $dividend->exDate?->format('Y-m-d'),
            'pay_date' => $dividend->payDate?->format('Y-m-d'),
            'dividend_type_code' => $dividend->dividendTypeCode,
            'source_country_code' => $dividend->sourceCountryCode,
            'gross_amount_eur' => $dividend->grossAmountEur,
            'foreign_tax_eur' => $dividend->foreignTaxEur,
            'original_currency' => $dividend->originalCurrency,
            'gross_amount_original' => $dividend->grossAmountOriginal,
            'foreign_tax_original' => $dividend->foreignTaxOriginal,
            'fx_rate_to_eur' => $dividend->fxRateToEur,
            'payer_ident_for_export' => $dividend->payerIdentForExport,
            'treaty_exemption_text' => $dividend->treatyExemptionText,
            'notes' => $dividend->notes,
            'is_voided' => $dividend->isVoided ? 1 : 0,
            'void_reason' => $dividend->voidReason,
            'updated_by' => $dividend->updatedBy,
            'id' => $dividend->id,
            'user_id' => $dividend->userId,
        ]);
    }

    /** @return Dividend[] */
    public function findByUserAndYear(int $userId, int $year): array
    {
        $start = sprintf('%d-01-01', $year);
        $end = sprintf('%d-12-31', $year);

        $sql = 'SELECT * FROM dividend WHERE user_id = :user_id AND received_date BETWEEN :start AND :end AND is_voided = 0 '
            . 'ORDER BY received_date, dividend_payer_id, id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'start' => $start,
            'end' => $end,
        ]);

        $rows = $stmt->fetchAll();
        $dividends = [];
        foreach ($rows as $row) {
            $dividends[] = $this->mapRow($row);
        }

        return $dividends;
    }

    private function mapRow(array $row): Dividend
    {
        return new Dividend(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            dividendPayerId: (int) $row['dividend_payer_id'],
            receivedDate: new DateTimeImmutable($row['received_date']),
            dividendTypeCode: $row['dividend_type_code'],
            sourceCountryCode: $row['source_country_code'],
            grossAmountEur: (float) $row['gross_amount_eur'],
            foreignTaxEur: $row['foreign_tax_eur'] !== null ? (float) $row['foreign_tax_eur'] : null,
            brokerAccountId: $row['broker_account_id'] !== null ? (int) $row['broker_account_id'] : null,
            instrumentId: $row['instrument_id'] !== null ? (int) $row['instrument_id'] : null,
            exDate: $row['ex_date'] ? new DateTimeImmutable($row['ex_date']) : null,
            payDate: $row['pay_date'] ? new DateTimeImmutable($row['pay_date']) : null,
            originalCurrency: $row['original_currency'],
            grossAmountOriginal: $row['gross_amount_original'] !== null ? (float) $row['gross_amount_original'] : null,
            foreignTaxOriginal: $row['foreign_tax_original'] !== null ? (float) $row['foreign_tax_original'] : null,
            fxRateToEur: $row['fx_rate_to_eur'] !== null ? (float) $row['fx_rate_to_eur'] : null,
            payerIdentForExport: $row['payer_ident_for_export'],
            treatyExemptionText: $row['treaty_exemption_text'],
            notes: $row['notes'],
            isVoided: (bool) $row['is_voided'],
            voidReason: $row['void_reason'],
            createdAt: isset($row['created_at']) ? new DateTimeImmutable($row['created_at']) : null,
            updatedAt: isset($row['updated_at']) ? new DateTimeImmutable($row['updated_at']) : null,
            createdBy: $row['created_by'] !== null ? (int) $row['created_by'] : null,
            updatedBy: $row['updated_by'] !== null ? (int) $row['updated_by'] : null,
        );
    }
}
