<?php

namespace App\Support\Payments;

final class GatewayCharge
{
    public const PAID = 'paid';

    public const PENDING = 'pending';

    public const FAILED = 'failed';

    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly string $status,
        public readonly ?string $chargeId = null,
        public readonly ?float $amount = null,
        public readonly ?string $authorizeUri = null,
        public readonly ?string $failureCode = null,
        public readonly ?string $failureMessage = null,
        public readonly array $raw = [],
    ) {}

    public function isPaid(): bool
    {
        return $this->status === self::PAID;
    }

    public function isFailed(): bool
    {
        return $this->status === self::FAILED;
    }
}
