<?php

declare(strict_types=1);

class DividendPayerController
{
    private DividendPayerService $payer_service;

    public function __construct()
    {
        require_once __DIR__ . '/../infrastructure/auth.php';
        require_auth();

        $this->payer_service = new DividendPayerService();
    }

    public function list(): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        $payers = $this->payer_service->list($user_id);

        require __DIR__ . '/../views/payers/list.php';
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

        $payer = !empty($old_input) ? (object) array_merge([
            'payer_name' => '',
            'payer_address' => '',
            'payer_country_code' => '',
            'payer_si_tax_id' => '',
            'payer_foreign_tax_id' => '',
            'default_source_country_code' => '',
            'default_dividend_type_code' => '',
        ], $old_input) : (object) [
            'payer_name' => '',
            'payer_address' => '',
            'payer_country_code' => '',
            'payer_si_tax_id' => '',
            'payer_foreign_tax_id' => '',
            'default_source_country_code' => '',
            'default_dividend_type_code' => '',
        ];

        require __DIR__ . '/../views/payers/form.php';
    }

    public function create_post(): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        $input = [
            'payer_name' => $_POST['payer_name'] ?? '',
            'payer_address' => $_POST['payer_address'] ?? '',
            'payer_country_code' => $_POST['payer_country_code'] ?? '',
            'payer_si_tax_id' => $_POST['payer_si_tax_id'] ?? '',
            'payer_foreign_tax_id' => $_POST['payer_foreign_tax_id'] ?? '',
            'default_source_country_code' => $_POST['default_source_country_code'] ?? '',
            'default_dividend_type_code' => $_POST['default_dividend_type_code'] ?? '',
        ];

        try {
            $this->payer_service->create($user_id, $input);
            header('Location: ?action=payers');
            exit;
        } catch (ValidationException $e) {
            $_SESSION['form_errors'] = $e->errors;
            $_SESSION['old_input'] = $input;
            header('Location: ?action=payer_new');
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
            $payer = $this->payer_service->get($user_id, $id);
        } catch (NotFoundException $e) {
            header('Location: ?action=payers');
            exit;
        }

        $errors = $_SESSION['form_errors'] ?? [];
        $old_input = $_SESSION['old_input'] ?? [];
        unset($_SESSION['form_errors'], $_SESSION['old_input']);

        // Use old_input if available (from validation error), otherwise use payer
        if (!empty($old_input)) {
            $payer_data = (object) array_merge([
                'id' => $id,
                'payer_name' => $payer->payer_name,
                'payer_address' => $payer->payer_address,
                'payer_country_code' => $payer->payer_country_code,
                'payer_si_tax_id' => $payer->payer_si_tax_id,
                'payer_foreign_tax_id' => $payer->payer_foreign_tax_id,
                'default_source_country_code' => $payer->default_source_country_code,
                'default_dividend_type_code' => $payer->default_dividend_type_code,
                'is_active' => $payer->is_active,
            ], $old_input);
        } else {
            $payer_data = (object) [
                'id' => $id,
                'payer_name' => $payer->payer_name,
                'payer_address' => $payer->payer_address,
                'payer_country_code' => $payer->payer_country_code,
                'payer_si_tax_id' => $payer->payer_si_tax_id,
                'payer_foreign_tax_id' => $payer->payer_foreign_tax_id,
                'default_source_country_code' => $payer->default_source_country_code,
                'default_dividend_type_code' => $payer->default_dividend_type_code,
                'is_active' => $payer->is_active,
            ];
        }

        require __DIR__ . '/../views/payers/form.php';
    }

    public function update_post(int $id): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        $input = [
            'payer_name' => $_POST['payer_name'] ?? '',
            'payer_address' => $_POST['payer_address'] ?? '',
            'payer_country_code' => $_POST['payer_country_code'] ?? '',
            'payer_si_tax_id' => $_POST['payer_si_tax_id'] ?? '',
            'payer_foreign_tax_id' => $_POST['payer_foreign_tax_id'] ?? '',
            'default_source_country_code' => $_POST['default_source_country_code'] ?? '',
            'default_dividend_type_code' => $_POST['default_dividend_type_code'] ?? '',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        try {
            $this->payer_service->update($user_id, $id, $input);
            header('Location: ?action=payers');
            exit;
        } catch (ValidationException $e) {
            $_SESSION['form_errors'] = $e->errors;
            $_SESSION['old_input'] = $input;
            header('Location: ?action=payer_edit&id=' . $id);
            exit;
        } catch (NotFoundException $e) {
            header('Location: ?action=payers');
            exit;
        }
    }
}

