<?php

declare(strict_types=1);

class TradeLotAllocation
{
    public function __construct(
        public readonly int $id,
        public readonly int $user_id,
        public readonly int $sell_trade_id,
        public readonly int $trade_lot_id,
        public readonly string $quantity_consumed,
        public readonly string $proceeds_eur,
        public readonly string $cost_basis_eur,
        public readonly string $realized_pnl_eur
    ) {
    }
}

