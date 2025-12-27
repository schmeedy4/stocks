<?php

declare(strict_types=1);

class DividendService
{
    private DividendRepository $dividend_repo;
    private DividendPayerRepository $payer_repo;
    private InstrumentRepository $instrument_repo;

    public function __construct()
    {
        $this->dividend_repo = new DividendRepository();
        $this->payer_repo = new DividendPayerRepository();
        $this->instrument_repo = new InstrumentRepository();
    }

    public function list(int $user_id, int $year): array
    {
        return $this->dividend_repo->list_by_year($user_id, $year);
    }

    public function get(int $user_id, int $id): Dividend
    {
        $dividend = $this->dividend_repo->find_by_id($user_id, $id);
        if ($dividend === null) {
            throw new NotFoundException('Dividend not found');
        }
        return $dividend;
    }

    public function create(int $user_id, array $input): int
    {
        // Derive dividend_payer_id from instrument if not provided
        $payer_id = null;
        if (isset($input['dividend_payer_id']) && $input['dividend_payer_id'] !== '') {
            $payer_id = (int) $input['dividend_payer_id'];
        } elseif (isset($input['instrument_id']) && $input['instrument_id'] !== '') {
            $instrument = $this->instrument_repo->find_by_id((int) $input['instrument_id']);
            if ($instrument !== null && $instrument->dividend_payer_id !== null) {
                $payer_id = $instrument->dividend_payer_id;
            }
        }

        // Get payer for validation and defaults
        $payer = null;
        if ($payer_id !== null) {
            $payer = $this->payer_repo->find_by_id($user_id, $payer_id);
        }

        $errors = $this->validate($input, $user_id, $payer_id, $payer);

        if (!empty($errors)) {
            throw new ValidationException('Validation failed', $errors);
        }

        // Apply defaults from payer
        $dividend_type_code = trim($input['dividend_type_code'] ?? '');
        if ($dividend_type_code === '' && $payer !== null && $payer->default_dividend_type_code !== null) {
            $dividend_type_code = $payer->default_dividend_type_code;
        }

        $source_country_code = trim($input['source_country_code'] ?? '');
        if ($source_country_code === '' && $payer !== null && $payer->default_source_country_code !== null) {
            $source_country_code = $payer->default_source_country_code;
        }

        $data = [
            'broker_account_id' => isset($input['broker_account_id']) && $input['broker_account_id'] !== '' ? (int) $input['broker_account_id'] : null,
            'instrument_id' => isset($input['instrument_id']) && $input['instrument_id'] !== '' ? (int) $input['instrument_id'] : null,
            'dividend_payer_id' => $payer_id,
            'received_date' => trim($input['received_date']),
            'ex_date' => isset($input['ex_date']) && $input['ex_date'] !== '' ? trim($input['ex_date']) : null,
            'pay_date' => isset($input['pay_date']) && $input['pay_date'] !== '' ? trim($input['pay_date']) : null,
            'dividend_type_code' => $dividend_type_code,
            'source_country_code' => strtoupper($source_country_code),
            'gross_amount_eur' => trim($input['gross_amount_eur']),
            'foreign_tax_eur' => isset($input['foreign_tax_eur']) && $input['foreign_tax_eur'] !== '' ? trim($input['foreign_tax_eur']) : null,
            'original_currency' => isset($input['original_currency']) && $input['original_currency'] !== '' ? strtoupper(trim($input['original_currency'])) : null,
            'gross_amount_original' => isset($input['gross_amount_original']) && $input['gross_amount_original'] !== '' ? trim($input['gross_amount_original']) : null,
            'foreign_tax_original' => isset($input['foreign_tax_original']) && $input['foreign_tax_original'] !== '' ? trim($input['foreign_tax_original']) : null,
            'fx_rate_to_eur' => isset($input['fx_rate_to_eur']) && $input['fx_rate_to_eur'] !== '' ? trim($input['fx_rate_to_eur']) : null,
            'payer_ident_for_export' => isset($input['payer_ident_for_export']) && $input['payer_ident_for_export'] !== '' ? trim($input['payer_ident_for_export']) : null,
            'treaty_exemption_text' => isset($input['treaty_exemption_text']) && $input['treaty_exemption_text'] !== '' ? trim($input['treaty_exemption_text']) : null,
            'notes' => isset($input['notes']) && $input['notes'] !== '' ? trim($input['notes']) : null,
        ];

        return $this->dividend_repo->create($user_id, $data);
    }

    public function update(int $user_id, int $id, array $input): void
    {
        $dividend = $this->dividend_repo->find_by_id($user_id, $id);
        if ($dividend === null) {
            throw new NotFoundException('Dividend not found');
        }

        // Derive dividend_payer_id from instrument if not provided
        $payer_id = null;
        if (isset($input['dividend_payer_id']) && $input['dividend_payer_id'] !== '') {
            $payer_id = (int) $input['dividend_payer_id'];
        } elseif (isset($input['instrument_id']) && $input['instrument_id'] !== '') {
            $instrument = $this->instrument_repo->find_by_id((int) $input['instrument_id']);
            if ($instrument !== null && $instrument->dividend_payer_id !== null) {
                $payer_id = $instrument->dividend_payer_id;
            }
        }

        // Get payer for validation
        $payer = null;
        if ($payer_id !== null) {
            $payer = $this->payer_repo->find_by_id($user_id, $payer_id);
        }

        $errors = $this->validate($input, $user_id, $payer_id, $payer);

        if (!empty($errors)) {
            throw new ValidationException('Validation failed', $errors);
        }

        // Apply defaults from payer (same as create)
        $dividend_type_code = trim($input['dividend_type_code'] ?? '');
        if ($dividend_type_code === '' && $payer !== null && $payer->default_dividend_type_code !== null) {
            $dividend_type_code = $payer->default_dividend_type_code;
        }

        $source_country_code = trim($input['source_country_code'] ?? '');
        if ($source_country_code === '' && $payer !== null && $payer->default_source_country_code !== null) {
            $source_country_code = $payer->default_source_country_code;
        }

        $data = [
            'broker_account_id' => isset($input['broker_account_id']) && $input['broker_account_id'] !== '' ? (int) $input['broker_account_id'] : null,
            'instrument_id' => isset($input['instrument_id']) && $input['instrument_id'] !== '' ? (int) $input['instrument_id'] : null,
            'dividend_payer_id' => $payer_id,
            'received_date' => trim($input['received_date']),
            'ex_date' => isset($input['ex_date']) && $input['ex_date'] !== '' ? trim($input['ex_date']) : null,
            'pay_date' => isset($input['pay_date']) && $input['pay_date'] !== '' ? trim($input['pay_date']) : null,
            'dividend_type_code' => $dividend_type_code,
            'source_country_code' => strtoupper($source_country_code),
            'gross_amount_eur' => trim($input['gross_amount_eur']),
            'foreign_tax_eur' => isset($input['foreign_tax_eur']) && $input['foreign_tax_eur'] !== '' ? trim($input['foreign_tax_eur']) : null,
            'original_currency' => isset($input['original_currency']) && $input['original_currency'] !== '' ? strtoupper(trim($input['original_currency'])) : null,
            'gross_amount_original' => isset($input['gross_amount_original']) && $input['gross_amount_original'] !== '' ? trim($input['gross_amount_original']) : null,
            'foreign_tax_original' => isset($input['foreign_tax_original']) && $input['foreign_tax_original'] !== '' ? trim($input['foreign_tax_original']) : null,
            'fx_rate_to_eur' => isset($input['fx_rate_to_eur']) && $input['fx_rate_to_eur'] !== '' ? trim($input['fx_rate_to_eur']) : null,
            'payer_ident_for_export' => isset($input['payer_ident_for_export']) && $input['payer_ident_for_export'] !== '' ? trim($input['payer_ident_for_export']) : null,
            'treaty_exemption_text' => isset($input['treaty_exemption_text']) && $input['treaty_exemption_text'] !== '' ? trim($input['treaty_exemption_text']) : null,
            'notes' => isset($input['notes']) && $input['notes'] !== '' ? trim($input['notes']) : null,
        ];

        $this->dividend_repo->update($user_id, $id, $data);
    }

    public function void_toggle(int $user_id, int $id): void
    {
        $dividend = $this->dividend_repo->find_by_id($user_id, $id);
        if ($dividend === null) {
            throw new NotFoundException('Dividend not found');
        }

        $this->dividend_repo->set_voided($user_id, $id, !$dividend->is_voided);
    }

    private function validate(array $input, int $user_id, ?int $payer_id, ?DividendPayer $payer): array
    {
        $errors = [];

        // dividend_payer_id required (after derivation attempt)
        if ($payer_id === null) {
            $errors['dividend_payer_id'] = 'Dividend payer is required';
        } else {
            // Verify payer belongs to user
            if ($payer === null) {
                $errors['dividend_payer_id'] = 'Dividend payer not found or does not belong to you';
            }
        }

        // received_date required
        if (!isset($input['received_date']) || trim($input['received_date']) === '') {
            $errors['received_date'] = 'Received date is required';
        } else {
            $date = trim($input['received_date']);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $errors['received_date'] = 'Invalid date format (expected YYYY-MM-DD)';
            }
        }

        // dividend_type_code required (may be defaulted from payer, but validate if provided)
        $dividend_type_code = trim($input['dividend_type_code'] ?? '');
        if ($dividend_type_code === '' && ($payer === null || $payer->default_dividend_type_code === null)) {
            $errors['dividend_type_code'] = 'Dividend type code is required';
        }

        // source_country_code required (may be defaulted from payer, but validate if provided)
        $source_country_code = trim($input['source_country_code'] ?? '');
        if ($source_country_code === '' && ($payer === null || $payer->default_source_country_code === null)) {
            $errors['source_country_code'] = 'Source country code is required';
        } elseif ($source_country_code !== '' && strlen($source_country_code) !== 2) {
            $errors['source_country_code'] = 'Source country code must be exactly 2 characters';
        } elseif ($source_country_code !== '' && !preg_match('/^[A-Z]+$/', strtoupper($source_country_code))) {
            $errors['source_country_code'] = 'Source country code must contain only letters';
        }

        // gross_amount_eur required, > 0
        if (!isset($input['gross_amount_eur']) || trim($input['gross_amount_eur']) === '') {
            $errors['gross_amount_eur'] = 'Gross amount EUR is required';
        } else {
            $amount = trim($input['gross_amount_eur']);
            if (!is_numeric($amount) || (float) $amount <= 0) {
                $errors['gross_amount_eur'] = 'Gross amount EUR must be greater than 0';
            }
        }

        // foreign_tax_eur optional, but if provided: >= 0 and <= gross_amount_eur
        if (isset($input['foreign_tax_eur']) && $input['foreign_tax_eur'] !== '') {
            $tax = trim($input['foreign_tax_eur']);
            if (!is_numeric($tax) || (float) $tax < 0) {
                $errors['foreign_tax_eur'] = 'Foreign tax EUR must be >= 0';
            } else {
                $gross = trim($input['gross_amount_eur'] ?? '0');
                if (is_numeric($gross) && (float) $tax > (float) $gross) {
                    $errors['foreign_tax_eur'] = 'Foreign tax EUR cannot exceed gross amount EUR';
                }
            }
        }

        // Special rule: if payer_country_code == 'SI', foreign_tax_eur must be NULL
        if ($payer !== null && $payer->payer_country_code === 'SI') {
            if (isset($input['foreign_tax_eur']) && $input['foreign_tax_eur'] !== '') {
                $errors['foreign_tax_eur'] = 'Foreign tax EUR must be empty for Slovenian payers';
            }
        }

        return $errors;
    }
}

