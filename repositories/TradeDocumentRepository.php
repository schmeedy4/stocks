<?php

declare(strict_types=1);

class TradeDocumentRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::get_connection();
    }

    /**
     * Link a document to a trade
     */
    public function link(int $trade_id, int $document_id): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO trade_document (trade_id, document_id)
            VALUES (:trade_id, :document_id)
            ON DUPLICATE KEY UPDATE trade_id = trade_id
        ');
        $stmt->execute([
            'trade_id' => $trade_id,
            'document_id' => $document_id,
        ]);
    }

    /**
     * Unlink a document from a trade
     */
    public function unlink(int $trade_id, int $document_id): void
    {
        $stmt = $this->db->prepare('
            DELETE FROM trade_document
            WHERE trade_id = :trade_id AND document_id = :document_id
        ');
        $stmt->execute([
            'trade_id' => $trade_id,
            'document_id' => $document_id,
        ]);
    }

    /**
     * Get all document IDs linked to a trade
     */
    public function get_document_ids(int $trade_id): array
    {
        $stmt = $this->db->prepare('
            SELECT document_id
            FROM trade_document
            WHERE trade_id = :trade_id
        ');
        $stmt->execute(['trade_id' => $trade_id]);

        $ids = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $ids[] = (int) $row['document_id'];
        }

        return $ids;
    }

    /**
     * Replace all links for a trade (used in transaction)
     */
    public function replace_links(int $trade_id, array $document_ids): void
    {
        // Delete all existing links
        $delete_stmt = $this->db->prepare('
            DELETE FROM trade_document
            WHERE trade_id = :trade_id
        ');
        $delete_stmt->execute(['trade_id' => $trade_id]);

        // Insert new links
        if (!empty($document_ids)) {
            $insert_stmt = $this->db->prepare('
                INSERT INTO trade_document (trade_id, document_id)
                VALUES (:trade_id, :document_id)
            ');

            foreach ($document_ids as $doc_id) {
                $insert_stmt->execute([
                    'trade_id' => $trade_id,
                    'document_id' => $doc_id,
                ]);
            }
        }
    }

    /**
     * Get all trade IDs linked to a document
     */
    public function get_trade_ids_for_document(int $document_id): array
    {
        $stmt = $this->db->prepare('
            SELECT trade_id
            FROM trade_document
            WHERE document_id = :document_id
        ');
        $stmt->execute(['document_id' => $document_id]);

        $ids = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $ids[] = (int) $row['trade_id'];
        }

        return $ids;
    }
}

