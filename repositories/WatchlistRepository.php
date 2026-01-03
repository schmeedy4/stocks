<?php

declare(strict_types=1);

class WatchlistRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::get_connection();
    }

    /**
     * Get or create the default watchlist for a user.
     * Uses transaction with locking to prevent concurrent creation of multiple defaults.
     */
    public function watchlist_get_default_id(int $user_id): int
    {
        $this->db->beginTransaction();
        
        try {
            // Lock any existing default watchlist for this user
            $stmt = $this->db->prepare('
                SELECT id FROM watchlist
                WHERE user_id = :user_id AND is_default = 1
                FOR UPDATE
            ');
            $stmt->execute(['user_id' => $user_id]);
            $row = $stmt->fetch();
            
            if ($row !== false) {
                // Default watchlist exists
                $this->db->commit();
                return (int) $row['id'];
            }
            
            // No default watchlist exists, create it
            $stmt = $this->db->prepare('
                INSERT INTO watchlist (user_id, name, is_default, created_at)
                VALUES (:user_id, :name, 1, NOW())
            ');
            $stmt->execute([
                'user_id' => $user_id,
                'name' => 'Default',
            ]);
            
            $watchlist_id = (int) $this->db->lastInsertId();
            $this->db->commit();
            
            return $watchlist_id;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Check if an instrument is in the user's default watchlist.
     */
    public function watchlist_is_in_default(int $user_id, int $instrument_id): bool
    {
        $default_id = $this->watchlist_get_default_id($user_id);
        
        $stmt = $this->db->prepare('
            SELECT 1 FROM watchlist_item
            WHERE watchlist_id = :watchlist_id AND instrument_id = :instrument_id
            LIMIT 1
        ');
        $stmt->execute([
            'watchlist_id' => $default_id,
            'instrument_id' => $instrument_id,
        ]);
        
        return $stmt->fetch() !== false;
    }

    /**
     * Get all instrument IDs in the user's default watchlist.
     * Returns an array of instrument IDs as keys (for fast lookup).
     */
    public function watchlist_get_default_instrument_ids(int $user_id): array
    {
        $default_id = $this->watchlist_get_default_id($user_id);
        
        $stmt = $this->db->prepare('
            SELECT instrument_id FROM watchlist_item
            WHERE watchlist_id = :watchlist_id
        ');
        $stmt->execute(['watchlist_id' => $default_id]);
        $rows = $stmt->fetchAll();
        
        $ids = [];
        foreach ($rows as $row) {
            $ids[(int) $row['instrument_id']] = true;
        }
        
        return $ids;
    }

    /**
     * Add an instrument to the user's default watchlist.
     * Uses INSERT IGNORE to handle duplicates gracefully.
     */
    public function watchlist_add_to_default(int $user_id, int $instrument_id): void
    {
        $default_id = $this->watchlist_get_default_id($user_id);
        $this->add_instrument_to_watchlist($user_id, $default_id, $instrument_id);
    }

    /**
     * Add an instrument to a specific watchlist.
     * Validates that the watchlist belongs to the user.
     * Uses INSERT IGNORE to handle duplicates gracefully.
     */
    public function add_instrument_to_watchlist(int $user_id, int $watchlist_id, int $instrument_id): void
    {
        // Verify watchlist belongs to user
        $watchlist = $this->find_by_id($user_id, $watchlist_id);
        if ($watchlist === null) {
            throw new NotFoundException('Watchlist not found');
        }
        
        $stmt = $this->db->prepare('
            INSERT IGNORE INTO watchlist_item (watchlist_id, instrument_id, created_at)
            VALUES (:watchlist_id, :instrument_id, NOW())
        ');
        $stmt->execute([
            'watchlist_id' => $watchlist_id,
            'instrument_id' => $instrument_id,
        ]);
    }

    /**
     * Check if an instrument is in a specific watchlist.
     */
    public function is_instrument_in_watchlist(int $user_id, int $watchlist_id, int $instrument_id): bool
    {
        // Verify watchlist belongs to user
        $watchlist = $this->find_by_id($user_id, $watchlist_id);
        if ($watchlist === null) {
            return false;
        }
        
        $stmt = $this->db->prepare('
            SELECT 1 FROM watchlist_item
            WHERE watchlist_id = :watchlist_id AND instrument_id = :instrument_id
            LIMIT 1
        ');
        $stmt->execute([
            'watchlist_id' => $watchlist_id,
            'instrument_id' => $instrument_id,
        ]);
        
        return $stmt->fetch() !== false;
    }

    /**
     * Remove an instrument from the user's default watchlist.
     */
    public function watchlist_remove_from_default(int $user_id, int $instrument_id): void
    {
        $default_id = $this->watchlist_get_default_id($user_id);
        $this->remove_instrument_from_watchlist($user_id, $default_id, $instrument_id);
    }

    /**
     * Remove an instrument from a specific watchlist.
     * Validates that the watchlist belongs to the user.
     */
    public function remove_instrument_from_watchlist(int $user_id, int $watchlist_id, int $instrument_id): void
    {
        // Verify watchlist belongs to user
        $watchlist = $this->find_by_id($user_id, $watchlist_id);
        if ($watchlist === null) {
            throw new NotFoundException('Watchlist not found');
        }
        
        $stmt = $this->db->prepare('
            DELETE FROM watchlist_item
            WHERE watchlist_id = :watchlist_id AND instrument_id = :instrument_id
        ');
        $stmt->execute([
            'watchlist_id' => $watchlist_id,
            'instrument_id' => $instrument_id,
        ]);
    }

    /**
     * List all instruments in the user's default watchlist.
     * Returns array of Instrument objects.
     */
    public function watchlist_list_default_instruments(int $user_id): array
    {
        $default_id = $this->watchlist_get_default_id($user_id);
        return $this->list_instruments_by_watchlist_id($user_id, $default_id);
    }

    /**
     * List all instruments in a specific watchlist.
     * Validates that the watchlist belongs to the user.
     * Returns array of Instrument objects.
     */
    public function list_instruments_by_watchlist_id(int $user_id, int $watchlist_id): array
    {
        // Verify watchlist belongs to user
        $watchlist = $this->find_by_id($user_id, $watchlist_id);
        if ($watchlist === null) {
            return [];
        }
        
        $stmt = $this->db->prepare('
            SELECT i.id, i.isin, i.ticker, i.name, i.instrument_type, i.country_code, i.trading_currency, i.dividend_payer_id, i.is_private
            FROM watchlist_item wi
            INNER JOIN instrument i ON wi.instrument_id = i.id
            WHERE wi.watchlist_id = :watchlist_id
            ORDER BY i.name ASC
        ');
        $stmt->execute(['watchlist_id' => $watchlist_id]);
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
     * List all watchlists for a user.
     * Returns array of Watchlist objects.
     */
    public function list_by_user(int $user_id): array
    {
        $stmt = $this->db->prepare('
            SELECT id, user_id, name, is_default, created_at
            FROM watchlist
            WHERE user_id = :user_id
            ORDER BY is_default DESC, name ASC
        ');
        $stmt->execute(['user_id' => $user_id]);
        $rows = $stmt->fetchAll();

        $watchlists = [];
        foreach ($rows as $row) {
            $watchlists[] = new Watchlist(
                (int) $row['id'],
                (int) $row['user_id'],
                $row['name'],
                (bool) $row['is_default'],
                $row['created_at']
            );
        }

        return $watchlists;
    }

    /**
     * Find a watchlist by ID, ensuring it belongs to the user.
     * Returns null if not found or doesn't belong to user.
     */
    public function find_by_id(int $user_id, int $watchlist_id): ?Watchlist
    {
        $stmt = $this->db->prepare('
            SELECT id, user_id, name, is_default, created_at
            FROM watchlist
            WHERE id = :id AND user_id = :user_id
        ');
        $stmt->execute([
            'id' => $watchlist_id,
            'user_id' => $user_id,
        ]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return new Watchlist(
            (int) $row['id'],
            (int) $row['user_id'],
            $row['name'],
            (bool) $row['is_default'],
            $row['created_at']
        );
    }

    /**
     * Create a new watchlist.
     * Returns the ID of the created watchlist.
     */
    public function create(int $user_id, string $name): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO watchlist (user_id, name, is_default, created_at)
            VALUES (:user_id, :name, 0, NOW())
        ');
        $stmt->execute([
            'user_id' => $user_id,
            'name' => $name,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update a watchlist name.
     * Cannot update is_default (use separate method if needed).
     */
    public function update(int $user_id, int $watchlist_id, string $name): void
    {
        $stmt = $this->db->prepare('
            UPDATE watchlist
            SET name = :name
            WHERE id = :id AND user_id = :user_id
        ');
        $stmt->execute([
            'id' => $watchlist_id,
            'user_id' => $user_id,
            'name' => $name,
        ]);
    }

    /**
     * Delete a watchlist.
     * Cascade will automatically delete watchlist_item rows.
     * Cannot delete the default watchlist.
     */
    public function delete(int $user_id, int $watchlist_id): void
    {
        // Prevent deletion of default watchlist
        $stmt = $this->db->prepare('
            DELETE FROM watchlist
            WHERE id = :id AND user_id = :user_id AND is_default = 0
        ');
        $stmt->execute([
            'id' => $watchlist_id,
            'user_id' => $user_id,
        ]);
    }

    /**
     * Get count of instruments in a watchlist.
     */
    public function get_instrument_count(int $watchlist_id): int
    {
        $stmt = $this->db->prepare('
            SELECT COUNT(*) as count
            FROM watchlist_item
            WHERE watchlist_id = :watchlist_id
        ');
        $stmt->execute(['watchlist_id' => $watchlist_id]);
        $row = $stmt->fetch();

        return $row ? (int) $row['count'] : 0;
    }
}

