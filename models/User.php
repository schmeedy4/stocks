<?php

declare(strict_types=1);

class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly ?string $password_hash,
        public readonly ?string $first_name,
        public readonly ?string $last_name,
        public readonly bool $is_active
    ) {
    }
}

