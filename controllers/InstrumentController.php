<?php

declare(strict_types=1);

class InstrumentController
{
    private InstrumentService $instrument_service;

    public function __construct()
    {
        require_once __DIR__ . '/../infrastructure/auth.php';
        require_auth();

        $this->instrument_service = new InstrumentService();
    }

    public function list(): void
    {
        $instruments = $this->instrument_service->list('');

        require __DIR__ . '/../views/instruments/list.php';
    }

    public function new(): void
    {
        $errors = $_SESSION['form_errors'] ?? [];
        $old_input = $_SESSION['old_input'] ?? [];
        unset($_SESSION['form_errors'], $_SESSION['old_input']);

        // Use old_input if available (from validation error), otherwise create empty object
        if (!empty($old_input)) {
            $instrument = (object) array_merge([
                'isin' => '',
                'ticker' => '',
                'name' => '',
                'instrument_type' => 'STOCK',
                'country_code' => '',
                'trading_currency' => '',
            ], $old_input);
        } else {
            $instrument = (object) [
                'isin' => '',
                'ticker' => '',
                'name' => '',
                'instrument_type' => 'STOCK',
                'country_code' => '',
                'trading_currency' => '',
            ];
        }

        require __DIR__ . '/../views/instruments/form.php';
    }

    public function create_post(): void
    {
        $input = [
            'name' => $_POST['name'] ?? '',
            'isin' => $_POST['isin'] ?? '',
            'ticker' => $_POST['ticker'] ?? '',
            'instrument_type' => $_POST['instrument_type'] ?? 'STOCK',
            'country_code' => $_POST['country_code'] ?? '',
            'trading_currency' => $_POST['trading_currency'] ?? '',
        ];

        try {
            $this->instrument_service->create($input);
            header('Location: ?action=instruments');
            exit;
        } catch (ValidationException $e) {
            $_SESSION['form_errors'] = $e->errors;
            $_SESSION['old_input'] = $input;
            header('Location: ?action=instrument_new');
            exit;
        }
    }

    public function edit(int $id): void
    {
        try {
            $instrument = $this->instrument_service->get($id);
        } catch (NotFoundException $e) {
            header('Location: ?action=instruments');
            exit;
        }

        $errors = $_SESSION['form_errors'] ?? [];
        $old_input = $_SESSION['old_input'] ?? [];
        unset($_SESSION['form_errors'], $_SESSION['old_input']);

        // Use old_input if available (from validation error), otherwise use instrument
        if (!empty($old_input)) {
            $instrument = (object) array_merge([
                'id' => $id,
                'isin' => '',
                'ticker' => '',
                'name' => '',
                'instrument_type' => 'STOCK',
                'country_code' => '',
                'trading_currency' => '',
            ], $old_input);
        }

        require __DIR__ . '/../views/instruments/form.php';
    }

    public function update_post(int $id): void
    {
        $input = [
            'name' => $_POST['name'] ?? '',
            'isin' => $_POST['isin'] ?? '',
            'ticker' => $_POST['ticker'] ?? '',
            'instrument_type' => $_POST['instrument_type'] ?? 'STOCK',
            'country_code' => $_POST['country_code'] ?? '',
            'trading_currency' => $_POST['trading_currency'] ?? '',
        ];

        try {
            $this->instrument_service->update($id, $input);
            header('Location: ?action=instruments');
            exit;
        } catch (ValidationException $e) {
            $_SESSION['form_errors'] = $e->errors;
            $_SESSION['old_input'] = $input;
            header('Location: ?action=instrument_edit&id=' . $id);
            exit;
        } catch (NotFoundException $e) {
            header('Location: ?action=instruments');
            exit;
        }
    }
}

