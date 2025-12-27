<?php

declare(strict_types=1);

class Dividend
{
    public function __construct(
        public readonly int $id,
        public readonly int $user_id,
        public readonly ?int $broker_account_id,
        public readonly ?int $instrument_id,
        public readonly int $dividend_payer_id,
        public readonly string $received_date,
        public readonly ?string $ex_date,
        public readonly ?string $pay_date,
        public readonly string $dividend_type_code,
        public readonly string $source_country_code,
        public readonly string $gross_amount_eur,
        public readonly ?string $foreign_tax_eur,
        public readonly ?string $original_currency,
        public readonly ?string $gross_amount_original,
        public readonly ?string $foreign_tax_original,
        public readonly ?string $fx_rate_to_eur,
        public readonly ?string $payer_ident_for_export,
        public readonly ?string $treaty_exemption_text,
        public readonly ?string $notes,
        public readonly bool $is_voided
    ) {
    }
}

