<?php

declare(strict_types=1);

class TradeController
{
    private TradeService $trade_service;
    private InstrumentRepository $instrument_repo;
    private BrokerAccountRepository $broker_repo;
    private DocumentRepository $document_repo;

    public function __construct()
    {
        require_once __DIR__ . '/../infrastructure/auth.php';
        require_auth();

        $this->trade_service = new TradeService();
        $this->instrument_repo = new InstrumentRepository();
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

        $trades = $this->trade_service->list_trades($user_id);

        // Get instruments for display
        $instruments = [];
        foreach ($trades as $trade) {
            if (!isset($instruments[$trade->instrument_id])) {
                $instruments[$trade->instrument_id] = $this->instrument_repo->find_by_id($trade->instrument_id);
            }
        }

        // Get document counts for each trade
        $document_counts = [];
        $trade_doc_repo = new TradeDocumentRepository();
        foreach ($trades as $trade) {
            $document_counts[$trade->id] = count($trade_doc_repo->get_document_ids($trade->id));
        }

        require __DIR__ . '/../views/trades/list.php';
    }

    public function new_buy(): void
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
        $broker_accounts = $this->broker_repo->list_by_user($user_id);

        $trade = !empty($old_input) ? (object) array_merge([
            'broker_account_id' => '',
            'instrument_id' => '',
            'trade_date' => date('Y-m-d'),
            'quantity' => '',
            'price_per_unit' => '',
            'trade_currency' => 'EUR',
            'fee_eur' => '',
            'notes' => '',
        ], $old_input) : (object) [
            'broker_account_id' => '',
            'instrument_id' => '',
            'trade_date' => date('Y-m-d'),
            'quantity' => '',
            'price_per_unit' => '',
            'trade_currency' => 'EUR',
            'fee_eur' => '',
            'notes' => '',
        ];

        require __DIR__ . '/../views/trades/form_buy.php';
    }

    public function create_buy_post(): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        $broker_account_id = $_POST['broker_account_id'] ?? '';
        if ($broker_account_id === '') {
            $broker_account_id = null;
        } elseif ($broker_account_id !== null) {
            $broker_account_id = (int) $broker_account_id;
        }

        $input = [
            'broker_account_id' => $broker_account_id,
            'instrument_id' => $_POST['instrument_id'] ?? '',
            'trade_date' => $_POST['trade_date'] ?? '',
            'quantity' => $_POST['quantity'] ?? '',
            'price_per_unit' => $_POST['price_per_unit'] ?? '',
            'trade_currency' => $_POST['trade_currency'] ?? '',
            'broker_fx_rate' => $_POST['broker_fx_rate'] ?? '',
            'fee_eur' => $_POST['fee_eur'] ?? '',
            'notes' => $_POST['notes'] ?? '',
        ];

        try {
            $this->trade_service->create_buy($user_id, $input);
            header('Location: ?action=trades');
            exit;
        } catch (ValidationException $e) {
            $_SESSION['form_errors'] = $e->errors;
            $_SESSION['old_input'] = $input;
            header('Location: ?action=trade_new_buy');
            exit;
        } catch (\Exception $e) {
            $_SESSION['form_errors'] = ['documents' => $e->getMessage()];
            $_SESSION['old_input'] = $input;
            header('Location: ?action=trade_new_buy');
            exit;
        }
    }

    public function new_sell(): void
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
        $broker_accounts = $this->broker_repo->list_by_user($user_id);

        $trade = !empty($old_input) ? (object) array_merge([
            'broker_account_id' => '',
            'instrument_id' => '',
            'trade_date' => date('Y-m-d'),
            'quantity' => '',
            'price_per_unit' => '',
            'trade_currency' => 'EUR',
            'fee_eur' => '',
            'notes' => '',
        ], $old_input) : (object) [
            'broker_account_id' => '',
            'instrument_id' => '',
            'trade_date' => date('Y-m-d'),
            'quantity' => '',
            'price_per_unit' => '',
            'trade_currency' => 'EUR',
            'fee_eur' => '',
            'notes' => '',
        ];

        require __DIR__ . '/../views/trades/form_sell.php';
    }

    public function create_sell_post(): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        $broker_account_id = $_POST['broker_account_id'] ?? '';
        if ($broker_account_id === '') {
            $broker_account_id = null;
        } elseif ($broker_account_id !== null) {
            $broker_account_id = (int) $broker_account_id;
        }

        $input = [
            'broker_account_id' => $broker_account_id,
            'instrument_id' => $_POST['instrument_id'] ?? '',
            'trade_date' => $_POST['trade_date'] ?? '',
            'quantity' => $_POST['quantity'] ?? '',
            'price_per_unit' => $_POST['price_per_unit'] ?? '',
            'trade_currency' => $_POST['trade_currency'] ?? '',
            'broker_fx_rate' => $_POST['broker_fx_rate'] ?? '',
            'fee_eur' => $_POST['fee_eur'] ?? '',
            'notes' => $_POST['notes'] ?? '',
        ];

        // Handle file uploads
        $uploaded_document_ids = $this->handle_file_uploads($user_id);
        if (!empty($uploaded_document_ids)) {
            $input['document_ids'] = array_unique($uploaded_document_ids);
        }

        try {
            $trade_id = $this->trade_service->create_sell_fifo($user_id, $input);
            // Redirect to edit page if documents were uploaded, otherwise to list
            if (!empty($uploaded_document_ids)) {
                header('Location: ?action=trade_edit&id=' . $trade_id);
            } else {
                header('Location: ?action=trades');
            }
            exit;
        } catch (ValidationException $e) {
            $_SESSION['form_errors'] = $e->errors;
            $_SESSION['old_input'] = $input;
            header('Location: ?action=trade_new_sell');
            exit;
        } catch (\Exception $e) {
            $_SESSION['form_errors'] = ['documents' => $e->getMessage()];
            $_SESSION['old_input'] = $input;
            header('Location: ?action=trade_new_sell');
            exit;
        }
    }

    public function view_sell(int $id): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        try {
            $data = $this->trade_service->get_sell_with_allocations($user_id, $id);
            $trade = $data['trade'];
            $allocations = $data['allocations'];

            // Get instrument for display
            $instrument = $this->instrument_repo->find_by_id($trade->instrument_id);

            require __DIR__ . '/../views/trades/view_sell.php';
        } catch (NotFoundException $e) {
            header('Location: ?action=trades');
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
            $trade = $this->trade_service->get_trade($user_id, $id);
        } catch (NotFoundException $e) {
            header('Location: ?action=trades');
            exit;
        }

        $errors = $_SESSION['form_errors'] ?? [];
        $old_input = $_SESSION['old_input'] ?? [];
        unset($_SESSION['form_errors'], $_SESSION['old_input']);

        $instruments = $this->instrument_repo->search('', 200);
        $broker_accounts = $this->broker_repo->list_by_user($user_id);
        
        // Get linked document IDs and fetch document details
        $linked_document_ids = $this->trade_service->get_linked_document_ids($id);
        $existing_documents = [];
        if (!empty($linked_document_ids)) {
            foreach ($linked_document_ids as $doc_id) {
                $doc = $this->document_repo->find_by_id($user_id, $doc_id);
                if ($doc !== null) {
                    $existing_documents[] = $doc;
                }
            }
        }

        // Use old_input if available (from validation error), otherwise use trade
        // Convert fx_rate_to_eur to broker_fx_rate for display (round to 4 decimals for clean display)
        $broker_fx_rate = '';
        if ($trade->trade_currency !== 'EUR' && $trade->fx_rate_to_eur !== '' && $trade->fx_rate_to_eur !== '0') {
            $broker_fx_rate = number_format(1 / (float)$trade->fx_rate_to_eur, 4, '.', '');
        }
        
        if (!empty($old_input)) {
            $trade_data = (object) array_merge([
                'id' => $id,
                'broker_account_id' => $trade->broker_account_id,
                'instrument_id' => $trade->instrument_id,
                'trade_date' => $trade->trade_date,
                'quantity' => $trade->quantity,
                'price_per_unit' => $trade->price_per_unit,
                'trade_currency' => $trade->trade_currency,
                'fx_rate_to_eur' => $trade->fx_rate_to_eur,
                'fee_eur' => $trade->fee_eur,
                'notes' => $trade->notes,
            ], $old_input);
            // If old_input doesn't have broker_fx_rate but has fx_rate_to_eur, convert it (round to 4 decimals)
            if (!isset($trade_data->broker_fx_rate) && isset($trade_data->fx_rate_to_eur) && $trade_data->trade_currency !== 'EUR' && $trade_data->fx_rate_to_eur !== '' && $trade_data->fx_rate_to_eur !== '0') {
                $trade_data->broker_fx_rate = number_format(1 / (float)$trade_data->fx_rate_to_eur, 4, '.', '');
            }
        } else {
            $trade_data = (object) [
                'id' => $id,
                'broker_account_id' => $trade->broker_account_id,
                'instrument_id' => $trade->instrument_id,
                'trade_date' => $trade->trade_date,
                'quantity' => $trade->quantity,
                'price_per_unit' => $trade->price_per_unit,
                'trade_currency' => $trade->trade_currency,
                'fx_rate_to_eur' => $trade->fx_rate_to_eur,
                'broker_fx_rate' => $broker_fx_rate,
                'fee_eur' => $trade->fee_eur,
                'notes' => $trade->notes,
            ];
        }

        $trade_type = $trade->trade_type;
        require __DIR__ . '/../views/trades/form_edit.php';
    }

    public function update_post(int $id): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        $broker_account_id = $_POST['broker_account_id'] ?? '';
        if ($broker_account_id === '') {
            $broker_account_id = null;
        } elseif ($broker_account_id !== null) {
            $broker_account_id = (int) $broker_account_id;
        }

        $input = [
            'broker_account_id' => $broker_account_id,
            'instrument_id' => $_POST['instrument_id'] ?? '',
            'trade_date' => $_POST['trade_date'] ?? '',
            'quantity' => $_POST['quantity'] ?? '',
            'price_per_unit' => $_POST['price_per_unit'] ?? '',
            'trade_currency' => $_POST['trade_currency'] ?? '',
            'broker_fx_rate' => $_POST['broker_fx_rate'] ?? '',
            'fee_eur' => $_POST['fee_eur'] ?? '',
            'notes' => $_POST['notes'] ?? '',
        ];

        // Handle file uploads
        $uploaded_document_ids = $this->handle_file_uploads($user_id);
        
        // Get currently linked document IDs (to preserve them if no new uploads)
        $current_linked_ids = $this->trade_service->get_linked_document_ids($id);
        
        // Combine uploaded documents with existing linked documents
        $all_document_ids = array_merge($current_linked_ids, $uploaded_document_ids);
        
        // Always set document_ids (even if empty) so service knows to update links
        $input['document_ids'] = array_unique($all_document_ids);

        try {
            $trade = $this->trade_service->get_trade($user_id, $id);
            
            if ($trade->trade_type === 'BUY') {
                $this->trade_service->update_buy($user_id, $id, $input);
            } else {
                $this->trade_service->update_sell($user_id, $id, $input);
            }

            // Redirect back to edit page to stay on same page after upload
            header('Location: ?action=trade_edit&id=' . $id);
            exit;
        } catch (ValidationException $e) {
            $_SESSION['form_errors'] = $e->errors;
            $_SESSION['old_input'] = $input;
            header('Location: ?action=trade_edit&id=' . $id);
            exit;
        } catch (NotFoundException $e) {
            header('Location: ?action=trades');
            exit;
        } catch (\Exception $e) {
            $_SESSION['form_errors'] = ['documents' => $e->getMessage()];
            $_SESSION['old_input'] = $input;
            header('Location: ?action=trade_edit&id=' . $id);
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
                $document_data = handle_document_upload($file, $user_id, 'TRADES');

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

        // Get trade_id from query string for redirect
        $trade_id = isset($_GET['trade_id']) ? (int) $_GET['trade_id'] : 0;

        // Build full file path
        $storage_base = __DIR__ . '/../storage/';
        $file_path = $storage_base . $document->storage_path;

        // Delete file from disk (if exists)
        if (file_exists($file_path)) {
            @unlink($file_path);
        }

        // Delete all links from trade_document table
        $trade_doc_repo = new TradeDocumentRepository();
        $linked_trade_ids = $trade_doc_repo->get_trade_ids_for_document($id);
        foreach ($linked_trade_ids as $t_id) {
            $trade_doc_repo->unlink($t_id, $id);
        }

        // Delete document record from database
        $this->document_repo->delete($user_id, $id);

        // Redirect back to trade edit page if trade_id provided, otherwise to trades list
        if ($trade_id > 0) {
            header('Location: ?action=trade_edit&id=' . $trade_id);
        } else {
            header('Location: ?action=trades');
        }
        exit;
    }

    /**
     * JSON endpoint: Get available quantity for an instrument.
     * GET /trades/sell/available?broker_account_id=..&instrument_id=..&trade_date=YYYY-MM-DD
     * Returns: { "available_qty": "12.000000" }
     */
    public function get_available_quantity_json(): void
    {
        header('Content-Type: application/json');

        $user_id = current_user_id();
        if ($user_id === null) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $instrument_id = isset($_GET['instrument_id']) ? (int) $_GET['instrument_id'] : 0;
        if ($instrument_id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid instrument_id']);
            exit;
        }

        $broker_account_id = isset($_GET['broker_account_id']) && $_GET['broker_account_id'] !== '' 
            ? (int) $_GET['broker_account_id'] 
            : null;
        
        $trade_date = isset($_GET['trade_date']) && $_GET['trade_date'] !== '' 
            ? $_GET['trade_date'] 
            : null;

        try {
            $available_qty = $this->trade_service->get_available_quantity($user_id, $instrument_id, $broker_account_id, $trade_date);
            echo json_encode(['available_qty' => $available_qty]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }

    /**
     * JSON endpoint: Get instruments list with availability for sell form.
     * GET /trades/sell/instruments?broker_account_id=..&trade_date=YYYY-MM-DD&include_zero=0|1
     * Returns: [{ "instrument_id": 1, "label": "AAPL - Apple Inc.", "available_qty": "12.000000" }, ...]
     */
    public function get_sell_instruments_json(): void
    {
        header('Content-Type: application/json');

        $user_id = current_user_id();
        if ($user_id === null) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $broker_account_id = isset($_GET['broker_account_id']) && $_GET['broker_account_id'] !== '' 
            ? (int) $_GET['broker_account_id'] 
            : null;
        
        $trade_date = isset($_GET['trade_date']) && $_GET['trade_date'] !== '' 
            ? $_GET['trade_date'] 
            : null;

        $include_zero = isset($_GET['include_zero']) && $_GET['include_zero'] === '1';

        try {
            $instruments = $this->trade_service->get_instruments_for_sell($user_id, $broker_account_id, $trade_date, $include_zero);
            echo json_encode($instruments);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }
}

