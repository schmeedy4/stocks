<?php

declare(strict_types=1);

class InstrumentRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::get_connection();
    }

    public function search(string $q = '', int $limit = 200): array
    {
        $sql = '
            SELECT id, isin, ticker, name, instrument_type, country_code, trading_currency, dividend_payer_id, is_private
            FROM instrument
        ';

        $params = [];

        if ($q !== '') {
            $sql .= ' WHERE name LIKE :q1 OR ticker LIKE :q2 OR isin LIKE :q3';
            $search_term = '%' . $q . '%';
            $params['q1'] = $search_term;
            $params['q2'] = $search_term;
            $params['q3'] = $search_term;
        }

        $sql .= ' ORDER BY name ASC LIMIT :limit';

        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $instruments = [];
        foreach ($rows as $row) {
            $instruments[] = new Instrument(
                (int) $row['id'],
                $row['isin'],
                $row['ticker'],
                $row['name'],
                $row['instrument_type'],
                $row['country_code'],
                $row['trading_currency'],
                $row['dividend_payer_id'] ? (int) $row['dividend_payer_id'] : null,
                (bool) $row['is_private']
            );
        }

        return $instruments;
    }

    /**
     * Search instruments with watchlist status for a user.
     * Returns array of arrays with instrument data and is_in_watchlist boolean.
     */
    public function search_with_watchlist_status(int $user_id, string $q = '', int $limit = 200): array
    {
        // Get default watchlist ID (will create if missing)
        $watchlist_repo = new WatchlistRepository();
        $default_watchlist_id = $watchlist_repo->watchlist_get_default_id($user_id);

        $sql = '
            SELECT 
                i.id, 
                i.isin, 
                i.ticker, 
                i.name, 
                i.instrument_type, 
                i.country_code, 
                i.trading_currency, 
                i.dividend_payer_id,
                i.is_private,
                CASE WHEN wli.instrument_id IS NULL THEN 0 ELSE 1 END AS is_in_watchlist
            FROM instrument i
            LEFT JOIN watchlist_item wli ON i.id = wli.instrument_id AND wli.watchlist_id = :watchlist_id
        ';

        $params = ['watchlist_id' => $default_watchlist_id];

        if ($q !== '') {
            $sql .= ' WHERE i.name LIKE :q OR i.ticker LIKE :q OR i.isin LIKE :q';
            $params['q'] = '%' . $q . '%';
        }

        $sql .= ' ORDER BY i.name ASC LIMIT :limit';

        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $results = [];
        foreach ($rows as $row) {
            $results[] = [
                'instrument' => new Instrument(
                    (int) $row['id'],
                    $row['isin'],
                    $row['ticker'],
                    $row['name'],
                    $row['instrument_type'],
                    $row['country_code'],
                    $row['trading_currency'],
                    $row['dividend_payer_id'] ? (int) $row['dividend_payer_id'] : null,
                    (bool) $row['is_private']
                ),
                'is_in_watchlist' => (bool) $row['is_in_watchlist'],
            ];
        }

        return $results;
    }

    public function find_by_id(int $id): ?Instrument
    {
        $stmt = $this->db->prepare('
            SELECT id, isin, ticker, name, instrument_type, country_code, trading_currency, dividend_payer_id, is_private
            FROM instrument
            WHERE id = :id
        ');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return new Instrument(
            (int) $row['id'],
            $row['isin'],
            $row['ticker'],
            $row['name'],
            $row['instrument_type'],
            $row['country_code'],
            $row['trading_currency'],
            $row['dividend_payer_id'] ? (int) $row['dividend_payer_id'] : null,
            (bool) $row['is_private']
        );
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO instrument (isin, ticker, name, instrument_type, country_code, trading_currency, dividend_payer_id, is_private)
            VALUES (:isin, :ticker, :name, :instrument_type, :country_code, :trading_currency, :dividend_payer_id, :is_private)
        ');
        $stmt->execute([
            'isin' => $data['isin'] ?? null,
            'ticker' => $data['ticker'] ?? null,
            'name' => $data['name'],
            'instrument_type' => $data['instrument_type'],
            'country_code' => $data['country_code'] ?? null,
            'trading_currency' => $data['trading_currency'] ?? null,
            'dividend_payer_id' => $data['dividend_payer_id'] ?? null,
            'is_private' => isset($data['is_private']) ? (int) $data['is_private'] : 0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare('
            UPDATE instrument
            SET isin = :isin,
                ticker = :ticker,
                name = :name,
                instrument_type = :instrument_type,
                country_code = :country_code,
                trading_currency = :trading_currency,
                dividend_payer_id = :dividend_payer_id,
                is_private = :is_private
            WHERE id = :id
        ');
        $stmt->execute([
            'id' => $id,
            'isin' => $data['isin'] ?? null,
            'ticker' => $data['ticker'] ?? null,
            'name' => $data['name'],
            'instrument_type' => $data['instrument_type'],
            'country_code' => $data['country_code'] ?? null,
            'trading_currency' => $data['trading_currency'] ?? null,
            'dividend_payer_id' => $data['dividend_payer_id'] ?? null,
            'is_private' => isset($data['is_private']) ? (int) $data['is_private'] : 0,
        ]);
    }
}


