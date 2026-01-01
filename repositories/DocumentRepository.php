<?php

declare(strict_types=1);

class DocumentRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::get_connection();
    }

    /**
     * Find document by SHA256 hash for a user (for deduplication)
     */
    public function find_by_sha256(int $user_id, string $sha256): ?Document
    {
        $stmt = $this->db->prepare('
            SELECT id, user_id, document_kind, broker_code, statement_period_from, statement_period_to,
                   original_filename, mime_type, file_size_bytes, storage_disk, storage_path, sha256,
                   parse_status, parse_error, extracted_data, extraction_notes,
                   created_trade_ids, created_dividend_ids, notes,
                   uploaded_at, created_at, updated_at
            FROM document
            WHERE user_id = :user_id AND sha256 = :sha256
        ');
        $stmt->execute(['user_id' => $user_id, 'sha256' => $sha256]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return $this->map_row_to_document($row);
    }

    /**
     * Find document by ID (with user_id check for security)
     */
    public function find_by_id(int $user_id, int $id): ?Document
    {
        $stmt = $this->db->prepare('
            SELECT id, user_id, document_kind, broker_code, statement_period_from, statement_period_to,
                   original_filename, mime_type, file_size_bytes, storage_disk, storage_path, sha256,
                   parse_status, parse_error, extracted_data, extraction_notes,
                   created_trade_ids, created_dividend_ids, notes,
                   uploaded_at, created_at, updated_at
            FROM document
            WHERE user_id = :user_id AND id = :id
        ');
        $stmt->execute(['user_id' => $user_id, 'id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return $this->map_row_to_document($row);
    }

    /**
     * List documents for a user (for selecting existing documents)
     */
    public function list_by_user(int $user_id, ?string $document_kind = null): array
    {
        $sql = '
            SELECT id, user_id, document_kind, broker_code, statement_period_from, statement_period_to,
                   original_filename, mime_type, file_size_bytes, storage_disk, storage_path, sha256,
                   parse_status, parse_error, extracted_data, extraction_notes,
                   created_trade_ids, created_dividend_ids, notes,
                   uploaded_at, created_at, updated_at
            FROM document
            WHERE user_id = :user_id
        ';

        $params = ['user_id' => $user_id];

        if ($document_kind !== null) {
            $sql .= ' AND document_kind = :document_kind';
            $params['document_kind'] = $document_kind;
        }

        $sql .= ' ORDER BY uploaded_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $documents = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $documents[] = $this->map_row_to_document($row);
        }

        return $documents;
    }

    /**
     * Create a new document record
     */
    public function create(int $user_id, array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO document (
                user_id, document_kind, broker_code, statement_period_from, statement_period_to,
                original_filename, mime_type, file_size_bytes, storage_disk, storage_path, sha256,
                parse_status, parse_error, extracted_data, extraction_notes,
                created_trade_ids, created_dividend_ids, notes
            )
            VALUES (
                :user_id, :document_kind, :broker_code, :statement_period_from, :statement_period_to,
                :original_filename, :mime_type, :file_size_bytes, :storage_disk, :storage_path, :sha256,
                :parse_status, :parse_error, :extracted_data, :extraction_notes,
                :created_trade_ids, :created_dividend_ids, :notes
            )
        ');

        $stmt->execute([
            'user_id' => $user_id,
            'document_kind' => $data['document_kind'],
            'broker_code' => $data['broker_code'] ?? null,
            'statement_period_from' => $data['statement_period_from'] ?? null,
            'statement_period_to' => $data['statement_period_to'] ?? null,
            'original_filename' => $data['original_filename'],
            'mime_type' => $data['mime_type'],
            'file_size_bytes' => $data['file_size_bytes'],
            'storage_disk' => $data['storage_disk'],
            'storage_path' => $data['storage_path'],
            'sha256' => $data['sha256'],
            'parse_status' => $data['parse_status'] ?? 'UPLOADED',
            'parse_error' => $data['parse_error'] ?? null,
            'extracted_data' => isset($data['extracted_data']) ? json_encode($data['extracted_data']) : null,
            'extraction_notes' => $data['extraction_notes'] ?? null,
            'created_trade_ids' => isset($data['created_trade_ids']) ? json_encode($data['created_trade_ids']) : null,
            'created_dividend_ids' => isset($data['created_dividend_ids']) ? json_encode($data['created_dividend_ids']) : null,
            'notes' => $data['notes'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Delete a document (with user_id check for security)
     */
    public function delete(int $user_id, int $id): void
    {
        $stmt = $this->db->prepare('
            DELETE FROM document
            WHERE user_id = :user_id AND id = :id
        ');
        $stmt->execute([
            'user_id' => $user_id,
            'id' => $id,
        ]);
    }

    private function map_row_to_document(array $row): Document
    {
        return new Document(
            (int) $row['id'],
            (int) $row['user_id'],
            $row['document_kind'],
            $row['broker_code'],
            $row['statement_period_from'],
            $row['statement_period_to'],
            $row['original_filename'],
            $row['mime_type'],
            (int) $row['file_size_bytes'],
            $row['storage_disk'],
            $row['storage_path'],
            $row['sha256'],
            $row['parse_status'],
            $row['parse_error'],
            $row['extracted_data'] ? json_decode($row['extracted_data'], true) : null,
            $row['extraction_notes'],
            $row['created_trade_ids'] ? json_decode($row['created_trade_ids'], true) : null,
            $row['created_dividend_ids'] ? json_decode($row['created_dividend_ids'], true) : null,
            $row['notes'],
            $row['uploaded_at'],
            $row['created_at'],
            $row['updated_at']
        );
    }
}

