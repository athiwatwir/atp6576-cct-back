<?php

namespace App\Support\Payments;

use RuntimeException;

class GatewayException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        string $message,
        public readonly ?string $failureCode = null,
        public readonly array $raw = [],
    ) {
        parent::__construct($message);
    }
}
