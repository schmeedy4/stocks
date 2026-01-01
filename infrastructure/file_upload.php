<?php

declare(strict_types=1);

/**
 * Handle file upload for documents
 * Returns document data array on success, throws exception on error
 * 
 * @param array $file $_FILES array element
 * @param int $user_id User ID for storage path
 * @param string $document_kind Document kind (DIVIDENDS, TRADES, etc.)
 * @return array Document data ready for DocumentRepository::create()
 * @throws \Exception On validation or upload errors
 */
function handle_document_upload(array $file, int $user_id, string $document_kind): array
{
    // Validate file was uploaded
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new \Exception('No file uploaded or invalid upload');
    }

    // Validate file size (max 10MB)
    $max_size = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $max_size) {
        throw new \Exception('File size exceeds 10MB limit');
    }

    // Validate file type (PDF or CSV)
    $allowed_mimes = ['application/pdf', 'text/csv', 'text/plain', 'application/vnd.ms-excel'];
    $allowed_extensions = ['pdf', 'csv', 'txt'];
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_extensions, true)) {
        throw new \Exception('Invalid file type. Only PDF and CSV files are allowed');
    }

    if (!in_array($file['type'], $allowed_mimes, true) && $file['type'] !== '') {
        // Some browsers may not send correct MIME type, so we check extension too
        // But if MIME type is provided and doesn't match, warn
    }

    // Read file content and compute SHA-256
    $file_content = file_get_contents($file['tmp_name']);
    if ($file_content === false) {
        throw new \Exception('Failed to read uploaded file');
    }

    $sha256 = hash('sha256', $file_content);

    // Create storage directory structure: storage/documents/{user_id}/{year}/{month}/
    $year = date('Y');
    $month = date('m');
    $storage_base = __DIR__ . '/../storage/documents/' . $user_id . '/' . $year . '/' . $month . '/';
    
    if (!is_dir($storage_base)) {
        if (!mkdir($storage_base, 0755, true)) {
            throw new \Exception('Failed to create storage directory');
        }
    }

    // Generate unique filename: {sha256}_{original_filename}
    $safe_filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
    $storage_filename = $sha256 . '_' . $safe_filename;
    $storage_path = $storage_base . $storage_filename;

    // Move uploaded file to storage
    if (!move_uploaded_file($file['tmp_name'], $storage_path)) {
        throw new \Exception('Failed to save uploaded file');
    }

    // Return relative path from storage root for database
    $relative_path = 'documents/' . $user_id . '/' . $year . '/' . $month . '/' . $storage_filename;

    return [
        'document_kind' => $document_kind,
        'broker_code' => null,
        'statement_period_from' => null,
        'statement_period_to' => null,
        'original_filename' => $file['name'],
        'mime_type' => $file['type'] ?: 'application/octet-stream',
        'file_size_bytes' => $file['size'],
        'storage_disk' => 'LOCAL',
        'storage_path' => $relative_path,
        'sha256' => $sha256,
        'parse_status' => 'UPLOADED',
        'parse_error' => null,
        'extracted_data' => null,
        'extraction_notes' => null,
        'created_trade_ids' => null,
        'created_dividend_ids' => null,
        'notes' => null,
    ];
}

