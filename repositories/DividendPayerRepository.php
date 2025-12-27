<?php

declare(strict_types=1);

class DividendPayerRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::get_connection();
    }

    public function list_by_user(int $user_id, bool $active_only = true): array
    {
        $sql = '
            SELECT id, user_id, payer_name, payer_address, payer_country_code,
                   payer_si_tax_id, payer_foreign_tax_id, default_source_country_code,
                   default_dividend_type_code, is_active
            FROM dividend_payer
            WHERE user_id = :user_id
        ';

        if ($active_only) {
            $sql .= ' AND is_active = 1';
        }

        $sql .= ' ORDER BY payer_name ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);

        $payers = [];
        foreach ($stmt->fetchAll() as $row) {
            $payers[] = new DividendPayer(
                (int) $row['id'],
                (int) $row['user_id'],
                $row['payer_name'],
                $row['payer_address'],
                $row['payer_country_code'],
                $row['payer_si_tax_id'],
                $row['payer_foreign_tax_id'],
                $row['default_source_country_code'],
                $row['default_dividend_type_code'],
                (bool) $row['is_active']
            );
        }

        return $payers;
    }

    public function find_by_id(int $user_id, int $payer_id): ?DividendPayer
    {
        $stmt = $this->db->prepare('
            SELECT id, user_id, payer_name, payer_address, payer_country_code,
                   payer_si_tax_id, payer_foreign_tax_id, default_source_country_code,
                   default_dividend_type_code, is_active
            FROM dividend_payer
            WHERE user_id = :user_id AND id = :payer_id
        ');
        $stmt->execute(['user_id' => $user_id, 'payer_id' => $payer_id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return new DividendPayer(
            (int) $row['id'],
            (int) $row['user_id'],
            $row['payer_name'],
            $row['payer_address'],
            $row['payer_country_code'],
            $row['payer_si_tax_id'],
            $row['payer_foreign_tax_id'],
            $row['default_source_country_code'],
            $row['default_dividend_type_code'],
            (bool) $row['is_active']
        );
    }

    public function create(int $user_id, array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO dividend_payer (
                user_id, payer_name, payer_address, payer_country_code,
                payer_si_tax_id, payer_foreign_tax_id, default_source_country_code,
                default_dividend_type_code, is_active
            )
            VALUES (
                :user_id, :payer_name, :payer_address, :payer_country_code,
                :payer_si_tax_id, :payer_foreign_tax_id, :default_source_country_code,
                :default_dividend_type_code, :is_active
            )
        ');
        $stmt->execute([
            'user_id' => $user_id,
            'payer_name' => $data['payer_name'],
            'payer_address' => $data['payer_address'],
            'payer_country_code' => $data['payer_country_code'],
            'payer_si_tax_id' => $data['payer_si_tax_id'] ?? null,
            'payer_foreign_tax_id' => $data['payer_foreign_tax_id'] ?? null,
            'default_source_country_code' => $data['default_source_country_code'] ?? null,
            'default_dividend_type_code' => $data['default_dividend_type_code'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $user_id, int $payer_id, array $data): void
    {
        $stmt = $this->db->prepare('
            UPDATE dividend_payer
            SET payer_name = :payer_name,
                payer_address = :payer_address,
                payer_country_code = :payer_country_code,
                payer_si_tax_id = :payer_si_tax_id,
                payer_foreign_tax_id = :payer_foreign_tax_id,
                default_source_country_code = :default_source_country_code,
                default_dividend_type_code = :default_dividend_type_code,
                is_active = :is_active
            WHERE user_id = :user_id AND id = :payer_id
        ');
        $stmt->execute([
            'user_id' => $user_id,
            'payer_id' => $payer_id,
            'payer_name' => $data['payer_name'],
            'payer_address' => $data['payer_address'],
            'payer_country_code' => $data['payer_country_code'],
            'payer_si_tax_id' => $data['payer_si_tax_id'] ?? null,
            'payer_foreign_tax_id' => $data['payer_foreign_tax_id'] ?? null,
            'default_source_country_code' => $data['default_source_country_code'] ?? null,
            'default_dividend_type_code' => $data['default_dividend_type_code'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
        ]);
    }
}

