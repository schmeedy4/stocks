<?php

declare(strict_types=1);

class InstrumentService
{
    private InstrumentRepository $instrument_repo;
    private DividendPayerRepository $payer_repo;

    public function __construct()
    {
        $this->instrument_repo = new InstrumentRepository();
        $this->payer_repo = new DividendPayerRepository();
    }

    public function list(string $q): array
    {
        return $this->instrument_repo->search($q, 200);
    }

    public function get(int $id): Instrument
    {
        $instrument = $this->instrument_repo->find_by_id($id);
        if ($instrument === null) {
            throw new NotFoundException('Instrument not found');
        }
        return $instrument;
    }

    public function create(array $input, int $user_id): int
    {
        $errors = $this->validate($input, $user_id);

        if (!empty($errors)) {
            throw new ValidationException('Validation failed', $errors);
        }

        $data = [
            'name' => trim($input['name']),
            'instrument_type' => $input['instrument_type'],
        ];

        if (isset($input['isin']) && $input['isin'] !== '') {
            $data['isin'] = strtoupper(trim($input['isin']));
        }

        if (isset($input['ticker']) && $input['ticker'] !== '') {
            $data['ticker'] = strtoupper(trim($input['ticker']));
        }

        if (isset($input['country_code']) && $input['country_code'] !== '') {
            $data['country_code'] = strtoupper(trim($input['country_code']));
        }

        if (isset($input['trading_currency']) && $input['trading_currency'] !== '') {
            $data['trading_currency'] = strtoupper(trim($input['trading_currency']));
        }

        if (isset($input['dividend_payer_id']) && $input['dividend_payer_id'] !== '') {
            $data['dividend_payer_id'] = (int) $input['dividend_payer_id'];
        }

        return $this->instrument_repo->create($data);
    }

    public function update(int $id, array $input, int $user_id): void
    {
        $errors = $this->validate($input, $user_id);

        if (!empty($errors)) {
            throw new ValidationException('Validation failed', $errors);
        }

        $instrument = $this->instrument_repo->find_by_id($id);
        if ($instrument === null) {
            throw new NotFoundException('Instrument not found');
        }

        $data = [
            'name' => trim($input['name']),
            'instrument_type' => $input['instrument_type'],
        ];

        if (isset($input['isin']) && $input['isin'] !== '') {
            $data['isin'] = strtoupper(trim($input['isin']));
        }

        if (isset($input['ticker']) && $input['ticker'] !== '') {
            $data['ticker'] = strtoupper(trim($input['ticker']));
        }

        if (isset($input['country_code']) && $input['country_code'] !== '') {
            $data['country_code'] = strtoupper(trim($input['country_code']));
        }

        if (isset($input['trading_currency']) && $input['trading_currency'] !== '') {
            $data['trading_currency'] = strtoupper(trim($input['trading_currency']));
        }

        if (isset($input['dividend_payer_id']) && $input['dividend_payer_id'] !== '') {
            $data['dividend_payer_id'] = (int) $input['dividend_payer_id'];
        } else {
            $data['dividend_payer_id'] = null;
        }

        $this->instrument_repo->update($id, $data);
    }

    private function validate(array $input, int $user_id): array
    {
        $errors = [];

        // name: required, 1..255
        if (!isset($input['name']) || trim($input['name']) === '') {
            $errors['name'] = 'Name is required';
        } elseif (strlen(trim($input['name'])) > 255) {
            $errors['name'] = 'Name must be 255 characters or less';
        }

        // isin: optional but if provided: 2..16 chars, uppercase + digits only (no spaces)
        if (isset($input['isin']) && $input['isin'] !== '') {
            $isin = trim($input['isin']);
            if (strlen($isin) < 2 || strlen($isin) > 16) {
                $errors['isin'] = 'ISIN must be between 2 and 16 characters';
            } elseif (!preg_match('/^[A-Z0-9]+$/', strtoupper($isin))) {
                $errors['isin'] = 'ISIN must contain only uppercase letters and digits';
            }
        }

        // ticker: optional but if provided: 1..32, uppercase letters/digits/dot/dash allowed
        if (isset($input['ticker']) && $input['ticker'] !== '') {
            $ticker = trim($input['ticker']);
            if (strlen($ticker) > 32) {
                $errors['ticker'] = 'Ticker must be 32 characters or less';
            } elseif (!preg_match('/^[A-Z0-9.\-]+$/i', $ticker)) {
                $errors['ticker'] = 'Ticker can only contain letters, digits, dots, and dashes';
            }
        }

        // instrument_type: must be one of: STOCK, ETF, ADR, BOND, OTHER
        $valid_types = ['STOCK', 'ETF', 'ADR', 'BOND', 'OTHER'];
        if (!isset($input['instrument_type']) || !in_array($input['instrument_type'], $valid_types, true)) {
            $errors['instrument_type'] = 'Invalid instrument type';
        }

        // trading_currency: optional but if provided: exactly 3 uppercase letters
        if (isset($input['trading_currency']) && $input['trading_currency'] !== '') {
            $currency = trim($input['trading_currency']);
            if (strlen($currency) !== 3) {
                $errors['trading_currency'] = 'Trading currency must be exactly 3 characters';
            } elseif (!preg_match('/^[A-Z]+$/', strtoupper($currency))) {
                $errors['trading_currency'] = 'Trading currency must contain only letters';
            }
        }

        // country_code: optional but if provided: exactly 2 uppercase letters
        if (isset($input['country_code']) && $input['country_code'] !== '') {
            $country = trim($input['country_code']);
            if (strlen($country) !== 2) {
                $errors['country_code'] = 'Country code must be exactly 2 characters';
            } elseif (!preg_match('/^[A-Z]+$/', strtoupper($country))) {
                $errors['country_code'] = 'Country code must contain only letters';
            }
        }

        // dividend_payer_id: optional, but if provided must belong to user
        if (isset($input['dividend_payer_id']) && $input['dividend_payer_id'] !== '') {
            $payer_id = (int) $input['dividend_payer_id'];
            $payer = $this->payer_repo->find_by_id($user_id, $payer_id);
            if ($payer === null) {
                $errors['dividend_payer_id'] = 'Dividend payer not found or does not belong to you';
            }
        }

        return $errors;
    }
}


