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

        $trades = $this->trade_service->list_trades($user_id);

        // Get instruments for display
        $instruments = [];
        foreach ($trades as $trade) {
            if (!isset($instruments[$trade->instrument_id])) {
                $instruments[$trade->instrument_id] = $this->instrument_repo->find_by_id($trade->instrument_id);
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
            'fx_rate_to_eur' => '1.00000000',
            'fee_eur' => '',
            'notes' => '',
        ], $old_input) : (object) [
            'broker_account_id' => '',
            'instrument_id' => '',
            'trade_date' => date('Y-m-d'),
            'quantity' => '',
            'price_per_unit' => '',
            'trade_currency' => 'EUR',
            'fx_rate_to_eur' => '1.00000000',
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
            'fx_rate_to_eur' => $_POST['fx_rate_to_eur'] ?? '',
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
            'fx_rate_to_eur' => '1.00000000',
            'fee_eur' => '',
            'notes' => '',
        ], $old_input) : (object) [
            'broker_account_id' => '',
            'instrument_id' => '',
            'trade_date' => date('Y-m-d'),
            'quantity' => '',
            'price_per_unit' => '',
            'trade_currency' => 'EUR',
            'fx_rate_to_eur' => '1.00000000',
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
            'fx_rate_to_eur' => $_POST['fx_rate_to_eur'] ?? '',
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
}

