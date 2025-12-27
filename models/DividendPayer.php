<?php

declare(strict_types=1);

class DividendPayer
{
    public function __construct(
        public readonly int $id,
        public readonly int $user_id,
        public readonly string $payer_name,
        public readonly string $payer_address,
        public readonly string $payer_country_code,
        public readonly ?string $payer_si_tax_id,
        public readonly ?string $payer_foreign_tax_id,
        public readonly ?string $default_source_country_code,
        public readonly ?string $default_dividend_type_code,
        public readonly bool $is_active
    ) {
    }
}

