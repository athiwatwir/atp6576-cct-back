<?php

namespace App\Support\Payments;

use App\Models\Order;
use App\Models\Payment;

interface CardGateway
{
    public function provider(): string;

    public function publicKey(): ?string;

    /**
     * @throws GatewayException
     */
    public function charge(Payment $payment, Order $order, string $cardToken, string $returnUri): GatewayCharge;

    public function inquire(Payment $payment): GatewayCharge;
}
