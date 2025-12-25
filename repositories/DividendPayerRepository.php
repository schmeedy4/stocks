<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\DividendPayer;
use PDO;

final class DividendPayerRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findById(int $userId, int $payerId): ?DividendPayer
    {
        $stmt = $this->pdo->prepare('SELECT * FROM dividend_payer WHERE user_id = :user_id AND id = :id AND is_active = 1');
        $stmt->execute([
            'user_id' => $userId,
            'id' => $payerId,
        ]);

        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        return new DividendPayer(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            payerName: $row['payer_name'],
            payerAddress: $row['payer_address'],
            payerCountryCode: $row['payer_country_code'],
            payerSiTaxId: $row['payer_si_tax_id'],
            payerForeignTaxId: $row['payer_foreign_tax_id'],
            defaultSourceCountryCode: $row['default_source_country_code'],
            defaultDividendTypeCode: $row['default_dividend_type_code'],
            isActive: (bool) $row['is_active'],
        );
    }

    /** @return DividendPayer[] */
    public function findActiveByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM dividend_payer WHERE user_id = :user_id AND is_active = 1 ORDER BY payer_name');
        $stmt->execute(['user_id' => $userId]);

        $payers = [];
        foreach ($stmt->fetchAll() as $row) {
            $payers[] = new DividendPayer(
                id: (int) $row['id'],
                userId: (int) $row['user_id'],
                payerName: $row['payer_name'],
                payerAddress: $row['payer_address'],
                payerCountryCode: $row['payer_country_code'],
                payerSiTaxId: $row['payer_si_tax_id'],
                payerForeignTaxId: $row['payer_foreign_tax_id'],
                defaultSourceCountryCode: $row['default_source_country_code'],
                defaultDividendTypeCode: $row['default_dividend_type_code'],
                isActive: (bool) $row['is_active'],
            );
        }

        return $payers;
    }
}
