<?php

declare(strict_types=1);

class TradeController
{
    private TradeService $trade_service;
    private InstrumentRepository $instrument_repo;
    private BrokerAccountRepository $broker_repo;

    public function __construct()
    {
        require_once __DIR__ . '/../infrastructure/auth.php';
        require_auth();

        $this->trade_service = new TradeService();
        $this->instrument_repo = new InstrumentRepository();
        $this->broker_repo = new BrokerAccountRepository();
    }

    public function list(): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        // Get year filter (default to current year)
        $selected_year = isset($_GET['year']) && $_GET['year'] !== '' 
            ? (int) $_GET['year'] 
            : (int) date('Y');
        
        $filters = [];
        if ($selected_year) {
            $filters['year'] = $selected_year;
        }

        $trades = $this->trade_service->list_trades($user_id, $filters);
        
        // Get min year for year selector
        $min_year = $this->trade_service->get_min_year($user_id);
        if ($min_year === null) {
            $min_year = (int) date('Y');
        }

        // Get instruments for display
        $instruments = [];
        foreach ($trades as $trade) {
            if (!isset($instruments[$trade->instrument_id])) {
                $instruments[$trade->instrument_id] = $this->instrument_repo->find_by_id($trade->instrument_id);
            }
        }

        // Get tax totals for SELL trades
        $sell_tax_totals = [];
        foreach ($trades as $trade) {
            if ($trade->trade_type === 'SELL') {
                $tax_totals = $this->trade_service->get_sell_tax_totals($user_id, $trade->id);
                if ($tax_totals !== null) {
                    $sell_tax_totals[$trade->id] = $tax_totals;
                }
            }
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

        try {
            $this->trade_service->create_sell_fifo($user_id, $input);
            header('Location: ?action=trades');
            exit;
        } catch (ValidationException $e) {
            $_SESSION['form_errors'] = $e->errors;
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

        try {
            $trade = $this->trade_service->get_trade($user_id, $id);
            
            if ($trade->trade_type === 'BUY') {
                $this->trade_service->update_buy($user_id, $id, $input);
            } else {
                $this->trade_service->update_sell($user_id, $id, $input);
            }

            header('Location: ?action=trades');
            exit;
        } catch (ValidationException $e) {
            $_SESSION['form_errors'] = $e->errors;
            $_SESSION['old_input'] = $input;
            header('Location: ?action=trade_edit&id=' . $id);
            exit;
        } catch (NotFoundException $e) {
            header('Location: ?action=trades');
            exit;
        }
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

