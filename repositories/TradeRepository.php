<?php

declare(strict_types=1);

class TradeRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::get_connection();
    }

    public function list_by_user(int $user_id, array $filters = []): array
    {
        $sql = '
            SELECT id, user_id, broker_account_id, instrument_id, trade_type, trade_date,
                   quantity, price_per_unit, trade_currency, fee_amount, fee_currency,
                   fx_rate_to_eur, total_value_eur, fee_eur, notes
            FROM trade
            WHERE user_id = :user_id AND is_voided = 0
        ';

        if (isset($filters['instrument_id'])) {
            $sql .= ' AND instrument_id = :instrument_id';
        }

        $sql .= ' ORDER BY trade_date DESC, id DESC';

        $stmt = $this->db->prepare($sql);
        $params = ['user_id' => $user_id];
        if (isset($filters['instrument_id'])) {
            $params['instrument_id'] = $filters['instrument_id'];
        }
        $stmt->execute($params);

        $trades = [];
        foreach ($stmt->fetchAll() as $row) {
            $trades[] = new Trade(
                (int) $row['id'],
                (int) $row['user_id'],
                $row['broker_account_id'] ? (int) $row['broker_account_id'] : null,
                (int) $row['instrument_id'],
                $row['trade_type'],
                $row['trade_date'],
                $row['quantity'],
                $row['price_per_unit'],
                $row['trade_currency'],
                $row['fee_amount'],
                $row['fee_currency'],
                $row['fx_rate_to_eur'],
                $row['total_value_eur'],
                $row['fee_eur'],
                $row['notes']
            );
        }

        return $trades;
    }

    public function find_by_id(int $user_id, int $trade_id): ?Trade
    {
        $stmt = $this->db->prepare('
            SELECT id, user_id, broker_account_id, instrument_id, trade_type, trade_date,
                   quantity, price_per_unit, trade_currency, fee_amount, fee_currency,
                   fx_rate_to_eur, total_value_eur, fee_eur, notes
            FROM trade
            WHERE user_id = :user_id AND id = :trade_id
        ');
        $stmt->execute(['user_id' => $user_id, 'trade_id' => $trade_id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return new Trade(
            (int) $row['id'],
            (int) $row['user_id'],
            $row['broker_account_id'] ? (int) $row['broker_account_id'] : null,
            (int) $row['instrument_id'],
            $row['trade_type'],
            $row['trade_date'],
            $row['quantity'],
            $row['price_per_unit'],
            $row['trade_currency'],
            $row['fee_amount'],
            $row['fee_currency'],
            $row['fx_rate_to_eur'],
            $row['total_value_eur'],
            $row['fee_eur'],
            $row['notes']
        );
    }

    public function create_trade(int $user_id, array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO trade (
                user_id, broker_account_id, instrument_id, trade_type, trade_date,
                quantity, price_per_unit, trade_currency, fee_amount, fee_currency,
                fx_rate_to_eur, total_value_eur, fee_eur, notes
            )
            VALUES (
                :user_id, :broker_account_id, :instrument_id, :trade_type, :trade_date,
                :quantity, :price_per_unit, :trade_currency, :fee_amount, :fee_currency,
                :fx_rate_to_eur, :total_value_eur, :fee_eur, :notes
            )
        ');
        $stmt->execute([
            'user_id' => $user_id,
            'broker_account_id' => $data['broker_account_id'] ?? null,
            'instrument_id' => $data['instrument_id'],
            'trade_type' => $data['trade_type'],
            'trade_date' => $data['trade_date'],
            'quantity' => $data['quantity'],
            'price_per_unit' => $data['price_per_unit'],
            'trade_currency' => $data['trade_currency'],
            'fee_amount' => $data['fee_amount'] ?? null,
            'fee_currency' => $data['fee_currency'] ?? null,
            'fx_rate_to_eur' => $data['fx_rate_to_eur'],
            'total_value_eur' => $data['total_value_eur'],
            'fee_eur' => $data['fee_eur'],
            'notes' => $data['notes'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }
}

