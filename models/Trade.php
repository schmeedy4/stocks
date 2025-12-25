<?php

declare(strict_types=1);

class Trade
{
    public function __construct(
        public readonly int $id,
        public readonly int $user_id,
        public readonly ?int $broker_account_id,
        public readonly int $instrument_id,
        public readonly string $trade_type,
        public readonly string $trade_date,
        public readonly string $quantity,
        public readonly string $price_per_unit,
        public readonly string $trade_currency,
        public readonly ?string $fee_amount,
        public readonly ?string $fee_currency,
        public readonly string $fx_rate_to_eur,
        public readonly string $total_value_eur,
        public readonly string $fee_eur,
        public readonly ?string $notes
    ) {
    }
}

