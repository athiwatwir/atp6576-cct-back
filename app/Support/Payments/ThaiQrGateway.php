<?php

namespace App\Support\Payments;

use App\Models\Order;
use App\Models\Payment;

interface ThaiQrGateway
{
    public function provider(): string;

    /**
     * @throws GatewayException
     */
    public function createQr(Payment $payment, Order $order): GatewayQr;

    public function inquire(Payment $payment): GatewayCharge;

    public function cancel(Payment $payment): void;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function verifyNotification(array $payload): bool;
}
