<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Payments\OnlinePayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __construct(private readonly OnlinePayment $payments) {}

    public function kgp(Request $request): JsonResponse
    {
        if (! $this->payments->handleKgpNotification($request->json()->all())) {
            return response()->json([
                'failure_code' => '1012',
                'failure_message' => 'Invalid Request',
            ], 400);
        }

        return response()->json((object) []);
    }

    public function kgpRefund(Request $request): JsonResponse
    {
        return match ($this->payments->handleKgpRefundNotification($request->json()->all())) {
            'invalid' => response()->json([
                'failure_code' => '1018',
                'failure_message' => 'Missing or invalid : refund_id, charge_id',
            ], 400),
            'unverified' => response()->json([
                'failure_code' => '1011',
                'failure_message' => 'Unable to Process',
            ], 503),
            default => response()->json((object) []),
        };
    }

    public function omise(Request $request): JsonResponse
    {
        $this->payments->handleOmiseEvent($request->json()->all());

        return response()->json((object) []);
    }
}
