<?php

declare(strict_types=1);

class DividendController
{
    private DividendService $dividend_service;
    private InstrumentRepository $instrument_repo;
    private DividendPayerRepository $payer_repo;
    private BrokerAccountRepository $broker_repo;

    public function __construct()
    {
        require_once __DIR__ . '/../infrastructure/auth.php';
        require_auth();

        $this->dividend_service = new DividendService();
        $this->instrument_repo = new InstrumentRepository();
        $this->payer_repo = new DividendPayerRepository();
        $this->broker_repo = new BrokerAccountRepository();
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

        try {
            $this->dividend_service->create($user_id, $input);
            header('Location: ?action=dividends');
            exit;
        } catch (ValidationException $e) {
            $_SESSION['form_errors'] = $e->errors;
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

        try {
            $this->dividend_service->update($user_id, $id, $input);
            header('Location: ?action=dividends');
            exit;
        } catch (ValidationException $e) {
            $_SESSION['form_errors'] = $e->errors;
            $_SESSION['old_input'] = $input;
            header('Location: ?action=dividend_edit&id=' . $id);
            exit;
        } catch (NotFoundException $e) {
            header('Location: ?action=dividends');
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
}

