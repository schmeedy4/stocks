<?php

declare(strict_types=1);

class TradeLot
{
    public function __construct(
        public readonly int $id,
        public readonly int $user_id,
        public readonly int $buy_trade_id,
        public readonly int $instrument_id,
        public readonly string $opened_date,
        public readonly string $quantity_opened,
        public readonly string $quantity_remaining,
        public readonly string $cost_basis_eur
    ) {
    }
}

