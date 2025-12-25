<?php

declare(strict_types=1);

class Instrument
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $isin,
        public readonly ?string $ticker,
        public readonly string $name,
        public readonly string $instrument_type,
        public readonly ?string $country_code,
        public readonly ?string $trading_currency
    ) {
    }
}


