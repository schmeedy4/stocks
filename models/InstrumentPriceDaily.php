<?php

declare(strict_types=1);

class InstrumentPriceDaily
{
    public function __construct(
        public readonly int $id,
        public readonly int $user_id,
        public readonly int $instrument_id,
        public readonly string $price_date,
        public readonly string $close_price,
        public readonly string $currency,
        public readonly string $source,
        public readonly string $fetched_at
    ) {
    }
}

