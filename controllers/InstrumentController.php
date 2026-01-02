<?php

declare(strict_types=1);

class InstrumentController
{
    private InstrumentService $instrument_service;
    private DividendPayerRepository $payer_repo;

    public function __construct()
    {
        require_once __DIR__ . '/../infrastructure/auth.php';
        require_auth();

        $this->instrument_service = new InstrumentService();
        $this->payer_repo = new DividendPayerRepository();
    }

    public function list(): void
    {
        $instruments = $this->instrument_service->list('');

        // Get sentiment counts for each instrument (30d and 90d)
        $news_repo = new NewsArticleRepository();
        $sentiment_counts_30d = [];
        $sentiment_counts_90d = [];
        foreach ($instruments as $instrument) {
            if ($instrument->ticker !== null && $instrument->ticker !== '') {
                $sentiment_counts_30d[$instrument->id] = $news_repo->get_sentiment_counts_days($instrument->ticker, 30);
                $sentiment_counts_90d[$instrument->id] = $news_repo->get_sentiment_counts_days($instrument->ticker, 90);
            } else {
                $empty_counts = [
                    'bullish' => 0,
                    'bearish' => 0,
                    'neutral' => 0,
                    'mixed' => 0,
                ];
                $sentiment_counts_30d[$instrument->id] = $empty_counts;
                $sentiment_counts_90d[$instrument->id] = $empty_counts;
            }
        }

        require __DIR__ . '/../views/instruments/list.php';
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

        $payers = $this->payer_repo->list_by_user($user_id, true);

        // Use old_input if available (from validation error), otherwise create empty object
        if (!empty($old_input)) {
            $instrument_data = (object) array_merge([
                'isin' => '',
                'ticker' => '',
                'name' => '',
                'instrument_type' => 'STOCK',
                'country_code' => '',
                'trading_currency' => '',
                'dividend_payer_id' => '',
            ], $old_input);
        } else {
            $instrument_data = (object) [
                'isin' => '',
                'ticker' => '',
                'name' => '',
                'instrument_type' => 'STOCK',
                'country_code' => '',
                'trading_currency' => '',
                'dividend_payer_id' => '',
            ];
        }

        require __DIR__ . '/../views/instruments/form.php';
    }

    public function create_post(): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        $input = [
            'name' => $_POST['name'] ?? '',
            'isin' => $_POST['isin'] ?? '',
            'ticker' => $_POST['ticker'] ?? '',
            'instrument_type' => $_POST['instrument_type'] ?? 'STOCK',
            'country_code' => $_POST['country_code'] ?? '',
            'trading_currency' => $_POST['trading_currency'] ?? '',
            'dividend_payer_id' => $_POST['dividend_payer_id'] ?? '',
        ];

        try {
            $this->instrument_service->create($input, $user_id);
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
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        try {
            $instrument = $this->instrument_service->get($id);
        } catch (NotFoundException $e) {
            header('Location: ?action=instruments');
            exit;
        }

        $errors = $_SESSION['form_errors'] ?? [];
        $old_input = $_SESSION['old_input'] ?? [];
        unset($_SESSION['form_errors'], $_SESSION['old_input']);

        $payers = $this->payer_repo->list_by_user($user_id, true);

        // Use old_input if available (from validation error), otherwise use instrument
        if (!empty($old_input)) {
            $instrument_data = (object) array_merge([
                'id' => $id,
                'isin' => $instrument->isin,
                'ticker' => $instrument->ticker,
                'name' => $instrument->name,
                'instrument_type' => $instrument->instrument_type,
                'country_code' => $instrument->country_code,
                'trading_currency' => $instrument->trading_currency,
                'dividend_payer_id' => $instrument->dividend_payer_id,
            ], $old_input);
        } else {
            $instrument_data = (object) [
                'id' => $id,
                'isin' => $instrument->isin,
                'ticker' => $instrument->ticker,
                'name' => $instrument->name,
                'instrument_type' => $instrument->instrument_type,
                'country_code' => $instrument->country_code,
                'trading_currency' => $instrument->trading_currency,
                'dividend_payer_id' => $instrument->dividend_payer_id,
            ];
        }

        require __DIR__ . '/../views/instruments/form.php';
    }

    public function update_post(int $id): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        $input = [
            'name' => $_POST['name'] ?? '',
            'isin' => $_POST['isin'] ?? '',
            'ticker' => $_POST['ticker'] ?? '',
            'instrument_type' => $_POST['instrument_type'] ?? 'STOCK',
            'country_code' => $_POST['country_code'] ?? '',
            'trading_currency' => $_POST['trading_currency'] ?? '',
            'dividend_payer_id' => $_POST['dividend_payer_id'] ?? '',
        ];

        try {
            $this->instrument_service->update($id, $input, $user_id);
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

