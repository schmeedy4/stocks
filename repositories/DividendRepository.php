<?php

declare(strict_types=1);

class DividendRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::get_connection();
    }

    public function list_by_year(int $user_id, int $year): array
    {
        $stmt = $this->db->prepare('
            SELECT id, user_id, broker_account_id, instrument_id, dividend_payer_id,
                   received_date, ex_date, pay_date, dividend_type_code, source_country_code,
                   gross_amount_eur, foreign_tax_eur, original_currency, gross_amount_original,
                   foreign_tax_original, fx_rate_to_eur, payer_ident_for_export,
                   treaty_exemption_text, notes, is_voided
            FROM dividend
            WHERE user_id = :user_id
              AND YEAR(received_date) = :year
            ORDER BY received_date DESC, id DESC
        ');
        $stmt->execute(['user_id' => $user_id, 'year' => $year]);

        $dividends = [];
        foreach ($stmt->fetchAll() as $row) {
            $dividends[] = new Dividend(
                (int) $row['id'],
                (int) $row['user_id'],
                $row['broker_account_id'] ? (int) $row['broker_account_id'] : null,
                $row['instrument_id'] ? (int) $row['instrument_id'] : null,
                (int) $row['dividend_payer_id'],
                $row['received_date'],
                $row['ex_date'],
                $row['pay_date'],
                $row['dividend_type_code'],
                $row['source_country_code'],
                $row['gross_amount_eur'],
                $row['foreign_tax_eur'],
                $row['original_currency'],
                $row['gross_amount_original'],
                $row['foreign_tax_original'],
                $row['fx_rate_to_eur'],
                $row['payer_ident_for_export'],
                $row['treaty_exemption_text'],
                $row['notes'],
                (bool) $row['is_voided']
            );
        }

        return $dividends;
    }

    public function find_by_id(int $user_id, int $id): ?Dividend
    {
        $stmt = $this->db->prepare('
            SELECT id, user_id, broker_account_id, instrument_id, dividend_payer_id,
                   received_date, ex_date, pay_date, dividend_type_code, source_country_code,
                   gross_amount_eur, foreign_tax_eur, original_currency, gross_amount_original,
                   foreign_tax_original, fx_rate_to_eur, payer_ident_for_export,
                   treaty_exemption_text, notes, is_voided
            FROM dividend
            WHERE user_id = :user_id AND id = :id
        ');
        $stmt->execute(['user_id' => $user_id, 'id' => $id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return new Dividend(
            (int) $row['id'],
            (int) $row['user_id'],
            $row['broker_account_id'] ? (int) $row['broker_account_id'] : null,
            $row['instrument_id'] ? (int) $row['instrument_id'] : null,
            (int) $row['dividend_payer_id'],
            $row['received_date'],
            $row['ex_date'],
            $row['pay_date'],
            $row['dividend_type_code'],
            $row['source_country_code'],
            $row['gross_amount_eur'],
            $row['foreign_tax_eur'],
            $row['original_currency'],
            $row['gross_amount_original'],
            $row['foreign_tax_original'],
            $row['fx_rate_to_eur'],
            $row['payer_ident_for_export'],
            $row['treaty_exemption_text'],
            $row['notes'],
            (bool) $row['is_voided']
        );
    }

    public function create(int $user_id, array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO dividend (
                user_id, broker_account_id, instrument_id, dividend_payer_id,
                received_date, ex_date, pay_date, dividend_type_code, source_country_code,
                gross_amount_eur, foreign_tax_eur, original_currency, gross_amount_original,
                foreign_tax_original, fx_rate_to_eur, payer_ident_for_export,
                treaty_exemption_text, notes, is_voided
            )
            VALUES (
                :user_id, :broker_account_id, :instrument_id, :dividend_payer_id,
                :received_date, :ex_date, :pay_date, :dividend_type_code, :source_country_code,
                :gross_amount_eur, :foreign_tax_eur, :original_currency, :gross_amount_original,
                :foreign_tax_original, :fx_rate_to_eur, :payer_ident_for_export,
                :treaty_exemption_text, :notes, :is_voided
            )
        ');
        $stmt->execute([
            'user_id' => $user_id,
            'broker_account_id' => $data['broker_account_id'] ?? null,
            'instrument_id' => $data['instrument_id'] ?? null,
            'dividend_payer_id' => $data['dividend_payer_id'],
            'received_date' => $data['received_date'],
            'ex_date' => $data['ex_date'] ?? null,
            'pay_date' => $data['pay_date'] ?? null,
            'dividend_type_code' => $data['dividend_type_code'],
            'source_country_code' => $data['source_country_code'],
            'gross_amount_eur' => $data['gross_amount_eur'],
            'foreign_tax_eur' => $data['foreign_tax_eur'] ?? null,
            'original_currency' => $data['original_currency'] ?? null,
            'gross_amount_original' => $data['gross_amount_original'] ?? null,
            'foreign_tax_original' => $data['foreign_tax_original'] ?? null,
            'fx_rate_to_eur' => $data['fx_rate_to_eur'] ?? null,
            'payer_ident_for_export' => $data['payer_ident_for_export'] ?? null,
            'treaty_exemption_text' => $data['treaty_exemption_text'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_voided' => 0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $user_id, int $id, array $data): void
    {
        $stmt = $this->db->prepare('
            UPDATE dividend
            SET broker_account_id = :broker_account_id,
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
                notes = :notes
            WHERE user_id = :user_id AND id = :id
        ');
        $stmt->execute([
            'user_id' => $user_id,
            'id' => $id,
            'broker_account_id' => $data['broker_account_id'] ?? null,
            'instrument_id' => $data['instrument_id'] ?? null,
            'dividend_payer_id' => $data['dividend_payer_id'],
            'received_date' => $data['received_date'],
            'ex_date' => $data['ex_date'] ?? null,
            'pay_date' => $data['pay_date'] ?? null,
            'dividend_type_code' => $data['dividend_type_code'],
            'source_country_code' => $data['source_country_code'],
            'gross_amount_eur' => $data['gross_amount_eur'],
            'foreign_tax_eur' => $data['foreign_tax_eur'] ?? null,
            'original_currency' => $data['original_currency'] ?? null,
            'gross_amount_original' => $data['gross_amount_original'] ?? null,
            'foreign_tax_original' => $data['foreign_tax_original'] ?? null,
            'fx_rate_to_eur' => $data['fx_rate_to_eur'] ?? null,
            'payer_ident_for_export' => $data['payer_ident_for_export'] ?? null,
            'treaty_exemption_text' => $data['treaty_exemption_text'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function set_voided(int $user_id, int $id, bool $is_voided): void
    {
        $stmt = $this->db->prepare('
            UPDATE dividend
            SET is_voided = :is_voided
            WHERE user_id = :user_id AND id = :id
        ');
        $stmt->execute([
            'user_id' => $user_id,
            'id' => $id,
            'is_voided' => $is_voided ? 1 : 0,
        ]);
    }
}

