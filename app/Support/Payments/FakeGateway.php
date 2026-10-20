<?php

namespace App\Support\Payments;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class FakeGateway implements CardGateway, ThaiQrGateway
{
    public const TOKEN_SUCCESS = 'tokn_fake_success';

    public const TOKEN_FAIL = 'tokn_fake_fail';

    public const TOKEN_3DS = 'tokn_fake_3ds';

    public function provider(): string
    {
        return 'fake';
    }

    public function publicKey(): ?string
    {
        return null;
    }

    public function createQr(Payment $payment, Order $order): GatewayQr
    {
        $seconds = min(86400, max(60, (int) config('payment.kgp.qr_expire_seconds', 600)));

        return new GatewayQr(
            qrId: 'qr_fake_'.Str::lower(Str::random(24)),
            image: $this->placeholderQr((string) $payment->reference),
            expiresAt: now()->addSeconds($seconds),
            accountName: (string) config('payment.merchant_name'),
            raw: ['driver' => 'fake'],
        );
    }

    public function inquire(Payment $payment): GatewayCharge
    {
        $state = (string) ($payment->metadata['fake_state'] ?? 'pending');

        return match ($state) {
            'paid' => new GatewayCharge(
                GatewayCharge::PAID,
                chargeId: $payment->metadata['charge_id'] ?? 'chrg_fake_'.$payment->id,
                amount: (float) $payment->amount,
                raw: ['driver' => 'fake'],
            ),
            'failed' => new GatewayCharge(
                GatewayCharge::FAILED,
                failureCode: 'fake_declined',
                failureMessage: 'ธนาคารปฏิเสธรายการ (โหมดทดสอบ)',
                raw: ['driver' => 'fake'],
            ),
            default => new GatewayCharge(GatewayCharge::PENDING, raw: ['driver' => 'fake']),
        };
    }

    public function cancel(Payment $payment): void {}

    public function verifyNotification(array $payload): bool
    {
        return false;
    }

    public function charge(Payment $payment, Order $order, string $cardToken, string $returnUri): GatewayCharge
    {
        $chargeId = 'chrg_fake_'.Str::lower(Str::random(20));

        return match ($cardToken) {
            self::TOKEN_SUCCESS => new GatewayCharge(GatewayCharge::PAID, $chargeId, (float) $payment->amount, raw: $this->card()),
            self::TOKEN_FAIL => new GatewayCharge(
                GatewayCharge::FAILED,
                $chargeId,
                failureCode: 'insufficient_fund',
                failureMessage: 'วงเงินในบัตรไม่พอ (โหมดทดสอบ)',
                raw: $this->card(),
            ),
            self::TOKEN_3DS => new GatewayCharge(
                GatewayCharge::PENDING,
                $chargeId,
                authorizeUri: URL::temporarySignedRoute('payment.fake-authorize', now()->addMinutes(15), [
                    'payment' => $payment->id,
                    'return' => $returnUri,
                ]),
                raw: $this->card(),
            ),
            default => throw new GatewayException('token บัตรไม่ถูกต้อง (โหมดทดสอบ)', 'invalid_card_token'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function card(): array
    {
        return ['driver' => 'fake', 'card' => ['brand' => 'Visa', 'last_digits' => '4242']];
    }

    private function placeholderQr(string $seed): string
    {
        $size = 29;
        $cell = 8;
        $hash = hash('sha256', $seed);
        $rects = '';

        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if ($this->isFinder($x, $y, $size)) {
                    continue;
                }
                $nibble = hexdec($hash[($x * 7 + $y * 13) % 64]);
                if ((($nibble + $x * $y) % 3) === 0) {
                    $rects .= '<rect x="'.($x * $cell).'" y="'.($y * $cell).'" width="'.$cell.'" height="'.$cell.'"/>';
                }
            }
        }

        foreach ([[0, 0], [$size - 7, 0], [0, $size - 7]] as [$fx, $fy]) {
            $rects .= '<rect x="'.($fx * $cell).'" y="'.($fy * $cell).'" width="'.(7 * $cell).'" height="'.(7 * $cell).'"/>'
                .'<rect x="'.(($fx + 1) * $cell).'" y="'.(($fy + 1) * $cell).'" width="'.(5 * $cell).'" height="'.(5 * $cell).'" fill="#fff"/>'
                .'<rect x="'.(($fx + 2) * $cell).'" y="'.(($fy + 2) * $cell).'" width="'.(3 * $cell).'" height="'.(3 * $cell).'"/>';
        }

        $full = $size * $cell;
        $mid = $full / 2;
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="-16 -16 '.($full + 32).' '.($full + 32).'">'
            .'<rect x="-16" y="-16" width="'.($full + 32).'" height="'.($full + 32).'" fill="#fff"/>'
            .'<g fill="#111">'.$rects.'</g>'
            .'<rect x="'.($mid - 40).'" y="'.($mid - 14).'" width="80" height="28" rx="4" fill="#fff" stroke="#00427A" stroke-width="2"/>'
            .'<text x="'.$mid.'" y="'.($mid + 6).'" font-family="sans-serif" font-size="16" font-weight="700" text-anchor="middle" fill="#00427A">TEST</text>'
            .'</svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    private function isFinder(int $x, int $y, int $size): bool
    {
        $inBox = fn (int $bx, int $by) => $x >= $bx && $x < $bx + 8 && $y >= $by && $y < $by + 8;

        return $inBox(0, 0) || $inBox($size - 8, 0) || $inBox(0, $size - 8);
    }
}
