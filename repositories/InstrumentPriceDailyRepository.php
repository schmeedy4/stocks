<?php

declare(strict_types=1);

class InstrumentPriceDailyRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::get_connection();
    }

    public function find_by_date(int $user_id, int $instrument_id, string $price_date): ?InstrumentPriceDaily
    {
        $stmt = $this->db->prepare('
            SELECT id, user_id, instrument_id, price_date, close_price, currency, source, fetched_at
            FROM instrument_price_daily
            WHERE user_id = :user_id AND instrument_id = :instrument_id AND price_date = :price_date
        ');
        $stmt->execute([
            'user_id' => $user_id,
            'instrument_id' => $instrument_id,
            'price_date' => $price_date,
        ]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return new InstrumentPriceDaily(
            (int) $row['id'],
            (int) $row['user_id'],
            (int) $row['instrument_id'],
            $row['price_date'],
            $row['close_price'],
            $row['currency'],
            $row['source'],
            $row['fetched_at']
        );
    }

    public function get_latest_price(int $user_id, int $instrument_id): ?InstrumentPriceDaily
    {
        $stmt = $this->db->prepare('
            SELECT id, user_id, instrument_id, price_date, close_price, currency, source, fetched_at
            FROM instrument_price_daily
            WHERE user_id = :user_id AND instrument_id = :instrument_id
            ORDER BY price_date DESC, fetched_at DESC
            LIMIT 1
        ');
        $stmt->execute([
            'user_id' => $user_id,
            'instrument_id' => $instrument_id,
        ]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return new InstrumentPriceDaily(
            (int) $row['id'],
            (int) $row['user_id'],
            (int) $row['instrument_id'],
            $row['price_date'],
            $row['close_price'],
            $row['currency'],
            $row['source'],
            $row['fetched_at']
        );
    }

    public function create(int $user_id, array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO instrument_price_daily (
                user_id, instrument_id, price_date, close_price, currency, source, fetched_at
            )
            VALUES (
                :user_id, :instrument_id, :price_date, :close_price, :currency, :source, NOW()
            )
        ');
        $stmt->execute([
            'user_id' => $user_id,
            'instrument_id' => $data['instrument_id'],
            'price_date' => $data['price_date'],
            'close_price' => $data['close_price'],
            'currency' => $data['currency'],
            'source' => $data['source'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function upsert(int $user_id, array $data): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO instrument_price_daily (
                user_id, instrument_id, price_date, close_price, currency, source, fetched_at
            )
            VALUES (
                :user_id, :instrument_id, :price_date, :close_price, :currency, :source, NOW()
            )
            ON DUPLICATE KEY UPDATE
                close_price = VALUES(close_price),
                currency = VALUES(currency),
                source = VALUES(source),
                fetched_at = NOW()
        ');
        $stmt->execute([
            'user_id' => $user_id,
            'instrument_id' => $data['instrument_id'],
            'price_date' => $data['price_date'],
            'close_price' => $data['close_price'],
            'currency' => $data['currency'],
            'source' => $data['source'],
        ]);
    }
}

