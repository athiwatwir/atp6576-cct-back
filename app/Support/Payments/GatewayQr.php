<?php

namespace App\Support\Payments;

use Carbon\CarbonInterface;

final class GatewayQr
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly string $qrId,
        public readonly ?string $image,
        public readonly CarbonInterface $expiresAt,
        public readonly ?string $accountName = null,
        public readonly array $raw = [],
    ) {}
}
