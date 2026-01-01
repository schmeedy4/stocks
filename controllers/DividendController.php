<?php

declare(strict_types=1);

class DividendController
{
    private DividendService $dividend_service;
    private InstrumentRepository $instrument_repo;
    private DividendPayerRepository $payer_repo;
    private BrokerAccountRepository $broker_repo;
    private DocumentRepository $document_repo;

    public function __construct()
    {
        require_once __DIR__ . '/../infrastructure/auth.php';
        require_auth();

        $this->dividend_service = new DividendService();
        $this->instrument_repo = new InstrumentRepository();
        $this->payer_repo = new DividendPayerRepository();
        $this->broker_repo = new BrokerAccountRepository();
        $this->document_repo = new DocumentRepository();
    }

    public function list(): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        $year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
        $dividends = $this->dividend_service->list($user_id, $year);

        // Get instruments and payers for display
        $instruments = [];
        $payers = [];
        foreach ($dividends as $dividend) {
            if ($dividend->instrument_id !== null && !isset($instruments[$dividend->instrument_id])) {
                $instruments[$dividend->instrument_id] = $this->instrument_repo->find_by_id($dividend->instrument_id);
            }
            if (!isset($payers[$dividend->dividend_payer_id])) {
                $payers[$dividend->dividend_payer_id] = $this->payer_repo->find_by_id($user_id, $dividend->dividend_payer_id);
            }
        }

        // Get document counts for each dividend
        $document_counts = [];
        $dividend_doc_repo = new DividendDocumentRepository();
        foreach ($dividends as $dividend) {
            $document_counts[$dividend->id] = count($dividend_doc_repo->get_document_ids($dividend->id));
        }

        require __DIR__ . '/../views/dividends/list.php';
    }

    public function new(): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        $errors = $_SESSION['form_errors'] ?? [];
        $old_input = $_SESSION['old_input'] ?? [];
        unset($_SESSION['form_errors'], $_SESSION['old_input']);

        $instruments = $this->instrument_repo->search('', 200);
        $payers = $this->payer_repo->list_by_user($user_id, true);
        $broker_accounts = $this->broker_repo->list_by_user($user_id);

        // Pre-select payer if instrument is selected and has default payer
        $selected_instrument_id = $old_input['instrument_id'] ?? $_GET['instrument_id'] ?? '';
        $selected_payer_id = $old_input['dividend_payer_id'] ?? '';
        if ($selected_instrument_id !== '' && $selected_payer_id === '') {
            $instrument = $this->instrument_repo->find_by_id((int) $selected_instrument_id);
            if ($instrument !== null && $instrument->dividend_payer_id !== null) {
                $selected_payer_id = (string) $instrument->dividend_payer_id;
            }
        }

        $dividend = !empty($old_input) ? (object) array_merge([
            'broker_account_id' => '',
            'instrument_id' => $selected_instrument_id,
            'dividend_payer_id' => $selected_payer_id,
            'received_date' => date('Y-m-d'),
            'ex_date' => '',
            'pay_date' => '',
            'dividend_type_code' => '',
            'source_country_code' => '',
            'gross_amount_eur' => '',
            'foreign_tax_eur' => '',
            'original_currency' => '',
            'gross_amount_original' => '',
            'foreign_tax_original' => '',
            'fx_rate_to_eur' => '',
            'payer_ident_for_export' => '',
            'treaty_exemption_text' => '',
            'notes' => '',
        ], $old_input) : (object) [
            'broker_account_id' => '',
            'instrument_id' => $selected_instrument_id,
            'dividend_payer_id' => $selected_payer_id,
            'received_date' => date('Y-m-d'),
            'ex_date' => '',
            'pay_date' => '',
            'dividend_type_code' => '',
            'source_country_code' => '',
            'gross_amount_eur' => '',
            'foreign_tax_eur' => '',
            'original_currency' => '',
            'gross_amount_original' => '',
            'foreign_tax_original' => '',
            'fx_rate_to_eur' => '',
            'payer_ident_for_export' => '',
            'treaty_exemption_text' => '',
            'notes' => '',
        ];

        require __DIR__ . '/../views/dividends/form.php';
    }

    public function create_post(): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        $input = [
            'broker_account_id' => $_POST['broker_account_id'] ?? '',
            'instrument_id' => $_POST['instrument_id'] ?? '',
            'dividend_payer_id' => $_POST['dividend_payer_id'] ?? '',
            'received_date' => $_POST['received_date'] ?? '',
            'ex_date' => $_POST['ex_date'] ?? '',
            'pay_date' => $_POST['pay_date'] ?? '',
            'dividend_type_code' => $_POST['dividend_type_code'] ?? '',
            'source_country_code' => $_POST['source_country_code'] ?? '',
            'gross_amount_eur' => $_POST['gross_amount_eur'] ?? '',
            'foreign_tax_eur' => $_POST['foreign_tax_eur'] ?? '',
            'original_currency' => $_POST['original_currency'] ?? '',
            'gross_amount_original' => $_POST['gross_amount_original'] ?? '',
            'foreign_tax_original' => $_POST['foreign_tax_original'] ?? '',
            'fx_rate_to_eur' => $_POST['fx_rate_to_eur'] ?? '',
            'payer_ident_for_export' => $_POST['payer_ident_for_export'] ?? '',
            'treaty_exemption_text' => $_POST['treaty_exemption_text'] ?? '',
            'notes' => $_POST['notes'] ?? '',
        ];

        // Handle file uploads
        $uploaded_document_ids = $this->handle_file_uploads($user_id);
        
        // Only use uploaded documents (no existing document selection)
        if (!empty($uploaded_document_ids)) {
            $input['document_ids'] = array_unique($uploaded_document_ids);
        }

        try {
            $dividend_id = $this->dividend_service->create($user_id, $input);
            // Redirect to edit page if documents were uploaded, otherwise to list
            if (!empty($uploaded_document_ids)) {
                header('Location: ?action=dividend_edit&id=' . $dividend_id);
            } else {
                header('Location: ?action=dividends');
            }
            exit;
        } catch (ValidationException $e) {
            $_SESSION['form_errors'] = $e->errors;
            $_SESSION['old_input'] = $input;
            header('Location: ?action=dividend_new');
            exit;
        } catch (\Exception $e) {
            $_SESSION['form_errors'] = ['documents' => $e->getMessage()];
            $_SESSION['old_input'] = $input;
            header('Location: ?action=dividend_new');
            exit;
        }
    }

    public function edit(int $id): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        try {
            $dividend = $this->dividend_service->get($user_id, $id);
        } catch (NotFoundException $e) {
            header('Location: ?action=dividends');
            exit;
        }

        $errors = $_SESSION['form_errors'] ?? [];
        $old_input = $_SESSION['old_input'] ?? [];
        unset($_SESSION['form_errors'], $_SESSION['old_input']);

        $instruments = $this->instrument_repo->search('', 200);
        $payers = $this->payer_repo->list_by_user($user_id, true);
        $broker_accounts = $this->broker_repo->list_by_user($user_id);
        
        // Get linked document IDs and fetch document details
        $linked_document_ids = $this->dividend_service->get_linked_document_ids($id);
        $existing_documents = [];
        if (!empty($linked_document_ids)) {
            foreach ($linked_document_ids as $doc_id) {
                $doc = $this->document_repo->find_by_id($user_id, $doc_id);
                if ($doc !== null) {
                    $existing_documents[] = $doc;
                }
            }
        }

        // Use old_input if available (from validation error), otherwise use dividend
        if (!empty($old_input)) {
            $dividend_data = (object) array_merge([
                'id' => $id,
                'broker_account_id' => $dividend->broker_account_id,
                'instrument_id' => $dividend->instrument_id,
                'dividend_payer_id' => $dividend->dividend_payer_id,
                'received_date' => $dividend->received_date,
                'ex_date' => $dividend->ex_date,
                'pay_date' => $dividend->pay_date,
                'dividend_type_code' => $dividend->dividend_type_code,
                'source_country_code' => $dividend->source_country_code,
                'gross_amount_eur' => $dividend->gross_amount_eur,
                'foreign_tax_eur' => $dividend->foreign_tax_eur,
                'original_currency' => $dividend->original_currency,
                'gross_amount_original' => $dividend->gross_amount_original,
                'foreign_tax_original' => $dividend->foreign_tax_original,
                'fx_rate_to_eur' => $dividend->fx_rate_to_eur,
                'payer_ident_for_export' => $dividend->payer_ident_for_export,
                'treaty_exemption_text' => $dividend->treaty_exemption_text,
                'notes' => $dividend->notes,
            ], $old_input);
        } else {
            $dividend_data = (object) [
                'id' => $id,
                'broker_account_id' => $dividend->broker_account_id,
                'instrument_id' => $dividend->instrument_id,
                'dividend_payer_id' => $dividend->dividend_payer_id,
                'received_date' => $dividend->received_date,
                'ex_date' => $dividend->ex_date,
                'pay_date' => $dividend->pay_date,
                'dividend_type_code' => $dividend->dividend_type_code,
                'source_country_code' => $dividend->source_country_code,
                'gross_amount_eur' => $dividend->gross_amount_eur,
                'foreign_tax_eur' => $dividend->foreign_tax_eur,
                'original_currency' => $dividend->original_currency,
                'gross_amount_original' => $dividend->gross_amount_original,
                'foreign_tax_original' => $dividend->foreign_tax_original,
                'fx_rate_to_eur' => $dividend->fx_rate_to_eur,
                'payer_ident_for_export' => $dividend->payer_ident_for_export,
                'treaty_exemption_text' => $dividend->treaty_exemption_text,
                'notes' => $dividend->notes,
            ];
        }

        require __DIR__ . '/../views/dividends/form.php';
    }

    public function update_post(int $id): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        $input = [
            'broker_account_id' => $_POST['broker_account_id'] ?? '',
            'instrument_id' => $_POST['instrument_id'] ?? '',
            'dividend_payer_id' => $_POST['dividend_payer_id'] ?? '',
            'received_date' => $_POST['received_date'] ?? '',
            'ex_date' => $_POST['ex_date'] ?? '',
            'pay_date' => $_POST['pay_date'] ?? '',
            'dividend_type_code' => $_POST['dividend_type_code'] ?? '',
            'source_country_code' => $_POST['source_country_code'] ?? '',
            'gross_amount_eur' => $_POST['gross_amount_eur'] ?? '',
            'foreign_tax_eur' => $_POST['foreign_tax_eur'] ?? '',
            'original_currency' => $_POST['original_currency'] ?? '',
            'gross_amount_original' => $_POST['gross_amount_original'] ?? '',
            'foreign_tax_original' => $_POST['foreign_tax_original'] ?? '',
            'fx_rate_to_eur' => $_POST['fx_rate_to_eur'] ?? '',
            'payer_ident_for_export' => $_POST['payer_ident_for_export'] ?? '',
            'treaty_exemption_text' => $_POST['treaty_exemption_text'] ?? '',
            'notes' => $_POST['notes'] ?? '',
        ];

        // Handle file uploads
        $uploaded_document_ids = $this->handle_file_uploads($user_id);
        
        // Get currently linked document IDs (to preserve them if no new uploads)
        $current_linked_ids = $this->dividend_service->get_linked_document_ids($id);
        
        // Combine uploaded documents with existing linked documents
        // If no new uploads, preserve existing links
        // If new uploads exist, add them to existing links
        $all_document_ids = array_merge($current_linked_ids, $uploaded_document_ids);
        
        // Always set document_ids (even if empty) so service knows to update links
        $input['document_ids'] = array_unique($all_document_ids);

        try {
            $this->dividend_service->update($user_id, $id, $input);
            // Redirect back to edit page to stay on same page after upload
            header('Location: ?action=dividend_edit&id=' . $id);
            exit;
        } catch (ValidationException $e) {
            $_SESSION['form_errors'] = $e->errors;
            $_SESSION['old_input'] = $input;
            header('Location: ?action=dividend_edit&id=' . $id);
            exit;
        } catch (NotFoundException $e) {
            header('Location: ?action=dividends');
            exit;
        } catch (\Exception $e) {
            $_SESSION['form_errors'] = ['documents' => $e->getMessage()];
            $_SESSION['old_input'] = $input;
            header('Location: ?action=dividend_edit&id=' . $id);
            exit;
        }
    }

    public function void_post(int $id): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        try {
            $this->dividend_service->void_toggle($user_id, $id);
            header('Location: ?action=dividends');
            exit;
        } catch (NotFoundException $e) {
            header('Location: ?action=dividends');
            exit;
        }
    }

    /**
     * Handle file uploads and return array of document IDs
     * @return array Document IDs
     */
    private function handle_file_uploads(int $user_id): array
    {
        require_once __DIR__ . '/../infrastructure/file_upload.php';

        $document_ids = [];

        if (!isset($_FILES['documents']) || !is_array($_FILES['documents']['name'])) {
            return $document_ids;
        }

        $files = $_FILES['documents'];
        $file_count = count($files['name']);

        for ($i = 0; $i < $file_count; $i++) {
            // Skip if no file uploaded for this slot
            if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            // Check for upload errors
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                throw new \Exception('File upload error: ' . $files['name'][$i]);
            }

            // Prepare file array for helper function
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i],
            ];

            try {
                // Handle upload and get document data
                $document_data = handle_document_upload($file, $user_id, 'DIVIDENDS');

                // Check for duplicate (same SHA256 for this user)
                $existing_doc = $this->document_repo->find_by_sha256($user_id, $document_data['sha256']);
                
                if ($existing_doc !== null) {
                    // Use existing document
                    $document_ids[] = $existing_doc->id;
                } else {
                    // Create new document record
                    $doc_id = $this->document_repo->create($user_id, $document_data);
                    $document_ids[] = $doc_id;
                }
            } catch (\Exception $e) {
                throw new \Exception('Failed to upload ' . $file['name'] . ': ' . $e->getMessage());
            }
        }

        return $document_ids;
    }

    public function download_document(int $id): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        // Find document and verify ownership
        $document = $this->document_repo->find_by_id($user_id, $id);
        if ($document === null) {
            http_response_code(404);
            echo 'Document not found';
            exit;
        }

        // Build full file path
        $storage_base = __DIR__ . '/../storage/';
        $file_path = $storage_base . $document->storage_path;

        // Verify file exists
        if (!file_exists($file_path) || !is_readable($file_path)) {
            http_response_code(404);
            echo 'File not found on disk';
            exit;
        }

        // Set headers for download
        header('Content-Type: ' . $document->mime_type);
        header('Content-Disposition: attachment; filename="' . addslashes($document->original_filename) . '"');
        header('Content-Length: ' . $document->file_size_bytes);
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        // Stream file
        readfile($file_path);
        exit;
    }

    public function delete_document(int $id): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        // Find document and verify ownership
        $document = $this->document_repo->find_by_id($user_id, $id);
        if ($document === null) {
            http_response_code(404);
            echo 'Document not found';
            exit;
        }

        // Get dividend_id from query string for redirect
        $dividend_id = isset($_GET['dividend_id']) ? (int) $_GET['dividend_id'] : 0;

        // Build full file path
        $storage_base = __DIR__ . '/../storage/';
        $file_path = $storage_base . $document->storage_path;

        // Delete file from disk (if exists)
        if (file_exists($file_path)) {
            @unlink($file_path);
        }

        // Delete all links from dividend_document table
        $dividend_doc_repo = new DividendDocumentRepository();
        $linked_dividend_ids = $dividend_doc_repo->get_dividend_ids_for_document($id);
        foreach ($linked_dividend_ids as $div_id) {
            $dividend_doc_repo->unlink($div_id, $id);
        }

        // Delete document record from database
        $this->document_repo->delete($user_id, $id);

        // Redirect back to dividend edit page if dividend_id provided, otherwise to dividends list
        if ($dividend_id > 0) {
            header('Location: ?action=dividend_edit&id=' . $dividend_id);
        } else {
            header('Location: ?action=dividends');
        }
        exit;
    }
}

