<?php

declare(strict_types=1);

class Watchlist
{
    public function __construct(
        public readonly int $id,
        public readonly int $user_id,
        public readonly string $name,
        public readonly bool $is_default,
        public readonly string $created_at
    ) {
    }
}

