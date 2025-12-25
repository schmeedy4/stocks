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
            SELECT id, isin, ticker, name, instrument_type, country_code, trading_currency
            FROM instrument
        ';

        $params = [];

        if ($q !== '') {
            $sql .= ' WHERE name LIKE :q OR ticker LIKE :q OR isin LIKE :q';
            $params['q'] = '%' . $q . '%';
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
                $row['trading_currency']
            );
        }

        return $instruments;
    }

    public function find_by_id(int $id): ?Instrument
    {
        $stmt = $this->db->prepare('
            SELECT id, isin, ticker, name, instrument_type, country_code, trading_currency
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
            $row['trading_currency']
        );
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO instrument (isin, ticker, name, instrument_type, country_code, trading_currency)
            VALUES (:isin, :ticker, :name, :instrument_type, :country_code, :trading_currency)
        ');
        $stmt->execute([
            'isin' => $data['isin'] ?? null,
            'ticker' => $data['ticker'] ?? null,
            'name' => $data['name'],
            'instrument_type' => $data['instrument_type'],
            'country_code' => $data['country_code'] ?? null,
            'trading_currency' => $data['trading_currency'] ?? null,
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
                trading_currency = :trading_currency
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
        ]);
    }
}


