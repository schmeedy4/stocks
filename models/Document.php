<?php

declare(strict_types=1);

class Document
{
    public function __construct(
        public readonly int $id,
        public readonly int $user_id,
        public readonly string $document_kind,
        public readonly ?string $broker_code,
        public readonly ?string $statement_period_from,
        public readonly ?string $statement_period_to,
        public readonly string $original_filename,
        public readonly string $mime_type,
        public readonly int $file_size_bytes,
        public readonly string $storage_disk,
        public readonly string $storage_path,
        public readonly string $sha256,
        public readonly string $parse_status,
        public readonly ?string $parse_error,
        public readonly ?array $extracted_data,
        public readonly ?string $extraction_notes,
        public readonly ?array $created_trade_ids,
        public readonly ?array $created_dividend_ids,
        public readonly ?string $notes,
        public readonly string $uploaded_at,
        public readonly string $created_at,
        public readonly ?string $updated_at
    ) {
    }
}

