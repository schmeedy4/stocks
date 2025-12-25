<?php

declare(strict_types=1);

class ValidationException extends Exception
{
    public function __construct(
        string $message,
        public readonly array $errors = []
    ) {
        parent::__construct($message);
    }
}

