<?php

declare(strict_types=1);

class DividendPayerService
{
    private DividendPayerRepository $payer_repo;

    public function __construct()
    {
        $this->payer_repo = new DividendPayerRepository();
    }

    public function list(int $user_id): array
    {
        return $this->payer_repo->list_by_user($user_id, true);
    }

    public function get(int $user_id, int $payer_id): DividendPayer
    {
        $payer = $this->payer_repo->find_by_id($user_id, $payer_id);
        if ($payer === null) {
            throw new NotFoundException('Dividend payer not found');
        }
        return $payer;
    }

    public function create(int $user_id, array $input): int
    {
        $errors = $this->validate($input);

        if (!empty($errors)) {
            throw new ValidationException('Validation failed', $errors);
        }

        $data = [
            'payer_name' => trim($input['payer_name']),
            'payer_address' => trim($input['payer_address']),
            'payer_country_code' => strtoupper(trim($input['payer_country_code'])),
            'payer_si_tax_id' => isset($input['payer_si_tax_id']) && $input['payer_si_tax_id'] !== '' ? trim($input['payer_si_tax_id']) : null,
            'payer_foreign_tax_id' => isset($input['payer_foreign_tax_id']) && $input['payer_foreign_tax_id'] !== '' ? trim($input['payer_foreign_tax_id']) : null,
            'default_source_country_code' => isset($input['default_source_country_code']) && $input['default_source_country_code'] !== '' ? strtoupper(trim($input['default_source_country_code'])) : null,
            'default_dividend_type_code' => isset($input['default_dividend_type_code']) && $input['default_dividend_type_code'] !== '' ? trim($input['default_dividend_type_code']) : null,
            'is_active' => 1,
        ];

        return $this->payer_repo->create($user_id, $data);
    }

    public function update(int $user_id, int $payer_id, array $input): void
    {
        $errors = $this->validate($input);

        if (!empty($errors)) {
            throw new ValidationException('Validation failed', $errors);
        }

        $payer = $this->payer_repo->find_by_id($user_id, $payer_id);
        if ($payer === null) {
            throw new NotFoundException('Dividend payer not found');
        }

        $data = [
            'payer_name' => trim($input['payer_name']),
            'payer_address' => trim($input['payer_address']),
            'payer_country_code' => strtoupper(trim($input['payer_country_code'])),
            'payer_si_tax_id' => isset($input['payer_si_tax_id']) && $input['payer_si_tax_id'] !== '' ? trim($input['payer_si_tax_id']) : null,
            'payer_foreign_tax_id' => isset($input['payer_foreign_tax_id']) && $input['payer_foreign_tax_id'] !== '' ? trim($input['payer_foreign_tax_id']) : null,
            'default_source_country_code' => isset($input['default_source_country_code']) && $input['default_source_country_code'] !== '' ? strtoupper(trim($input['default_source_country_code'])) : null,
            'default_dividend_type_code' => isset($input['default_dividend_type_code']) && $input['default_dividend_type_code'] !== '' ? trim($input['default_dividend_type_code']) : null,
            'is_active' => isset($input['is_active']) ? (int) $input['is_active'] : 1,
        ];

        $this->payer_repo->update($user_id, $payer_id, $data);
    }

    private function validate(array $input): array
    {
        $errors = [];

        // payer_name required
        if (!isset($input['payer_name']) || trim($input['payer_name']) === '') {
            $errors['payer_name'] = 'Payer name is required';
        } elseif (strlen(trim($input['payer_name'])) > 255) {
            $errors['payer_name'] = 'Payer name must be 255 characters or less';
        }

        // payer_address required
        if (!isset($input['payer_address']) || trim($input['payer_address']) === '') {
            $errors['payer_address'] = 'Payer address is required';
        } elseif (strlen(trim($input['payer_address'])) > 255) {
            $errors['payer_address'] = 'Payer address must be 255 characters or less';
        }

        // payer_country_code required, exactly 2 uppercase letters
        if (!isset($input['payer_country_code']) || trim($input['payer_country_code']) === '') {
            $errors['payer_country_code'] = 'Payer country code is required';
        } elseif (strlen(trim($input['payer_country_code'])) !== 2) {
            $errors['payer_country_code'] = 'Country code must be exactly 2 characters';
        } elseif (!preg_match('/^[A-Z]+$/', strtoupper(trim($input['payer_country_code'])))) {
            $errors['payer_country_code'] = 'Country code must contain only letters';
        }

        // default_source_country_code optional, but if provided: exactly 2 uppercase letters
        if (isset($input['default_source_country_code']) && $input['default_source_country_code'] !== '') {
            $code = trim($input['default_source_country_code']);
            if (strlen($code) !== 2) {
                $errors['default_source_country_code'] = 'Source country code must be exactly 2 characters';
            } elseif (!preg_match('/^[A-Z]+$/', strtoupper($code))) {
                $errors['default_source_country_code'] = 'Source country code must contain only letters';
            }
        }

        return $errors;
    }
}

