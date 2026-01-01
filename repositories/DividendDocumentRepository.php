<?php

declare(strict_types=1);

class DividendDocumentRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::get_connection();
    }

    /**
     * Link a document to a dividend
     */
    public function link(int $dividend_id, int $document_id): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO dividend_document (dividend_id, document_id)
            VALUES (:dividend_id, :document_id)
            ON DUPLICATE KEY UPDATE dividend_id = dividend_id
        ');
        $stmt->execute([
            'dividend_id' => $dividend_id,
            'document_id' => $document_id,
        ]);
    }

    /**
     * Unlink a document from a dividend
     */
    public function unlink(int $dividend_id, int $document_id): void
    {
        $stmt = $this->db->prepare('
            DELETE FROM dividend_document
            WHERE dividend_id = :dividend_id AND document_id = :document_id
        ');
        $stmt->execute([
            'dividend_id' => $dividend_id,
            'document_id' => $document_id,
        ]);
    }

    /**
     * Get all document IDs linked to a dividend
     */
    public function get_document_ids(int $dividend_id): array
    {
        $stmt = $this->db->prepare('
            SELECT document_id
            FROM dividend_document
            WHERE dividend_id = :dividend_id
        ');
        $stmt->execute(['dividend_id' => $dividend_id]);

        $ids = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $ids[] = (int) $row['document_id'];
        }

        return $ids;
    }

    /**
     * Replace all links for a dividend (used in transaction)
     */
    public function replace_links(int $dividend_id, array $document_ids): void
    {
        // Delete all existing links
        $delete_stmt = $this->db->prepare('
            DELETE FROM dividend_document
            WHERE dividend_id = :dividend_id
        ');
        $delete_stmt->execute(['dividend_id' => $dividend_id]);

        // Insert new links
        if (!empty($document_ids)) {
            $insert_stmt = $this->db->prepare('
                INSERT INTO dividend_document (dividend_id, document_id)
                VALUES (:dividend_id, :document_id)
            ');

            foreach ($document_ids as $doc_id) {
                $insert_stmt->execute([
                    'dividend_id' => $dividend_id,
                    'document_id' => $doc_id,
                ]);
            }
        }
    }

    /**
     * Get all dividend IDs linked to a document
     */
    public function get_dividend_ids_for_document(int $document_id): array
    {
        $stmt = $this->db->prepare('
            SELECT dividend_id
            FROM dividend_document
            WHERE document_id = :document_id
        ');
        $stmt->execute(['document_id' => $document_id]);

        $ids = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $ids[] = (int) $row['dividend_id'];
        }

        return $ids;
    }
}

