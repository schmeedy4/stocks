<?php

declare(strict_types=1);

class CorporateActionController
{
    private CorporateActionService $corporate_action_service;
    private InstrumentRepository $instrument_repo;

    public function __construct()
    {
        require_once __DIR__ . '/../infrastructure/auth.php';
        require_auth();

        $this->corporate_action_service = new CorporateActionService();
        $this->instrument_repo = new InstrumentRepository();
    }

    public function show_split_form(): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        $errors = $_SESSION['form_errors'] ?? [];
        $old_input = $_SESSION['old_input'] ?? [];
        $success_message = $_SESSION['success_message'] ?? null;
        unset($_SESSION['form_errors'], $_SESSION['old_input'], $_SESSION['success_message']);

        $instruments = $this->instrument_repo->search('', 200);

        $split_data = !empty($old_input) ? (object) array_merge([
            'instrument_id' => '',
            'split_date' => date('Y-m-d'),
            'ratio_from' => '1',
            'ratio_to' => '1',
        ], $old_input) : (object) [
            'instrument_id' => '',
            'split_date' => date('Y-m-d'),
            'ratio_from' => '1',
            'ratio_to' => '1',
        ];

        require __DIR__ . '/../views/corporate_actions/split_form.php';
    }

    public function apply_split_post(): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        $input = [
            'instrument_id' => $_POST['instrument_id'] ?? '',
            'split_date' => $_POST['split_date'] ?? '',
            'ratio_from' => $_POST['ratio_from'] ?? '',
            'ratio_to' => $_POST['ratio_to'] ?? '',
        ];

        try {
            $this->corporate_action_service->apply_stock_split(
                $user_id,
                (int) $input['instrument_id'],
                $input['split_date'],
                (int) $input['ratio_from'],
                (int) $input['ratio_to']
            );

            $_SESSION['success_message'] = 'Stock split applied successfully. All open lots have been adjusted.';
            header('Location: ?action=corporate_actions');
            exit;
        } catch (ValidationException $e) {
            $_SESSION['form_errors'] = $e->errors;
            $_SESSION['old_input'] = $input;
            header('Location: ?action=corporate_actions');
            exit;
        }
    }
}

