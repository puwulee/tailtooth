<?php

namespace App\Services\Payment;

use App\Models\Registration;
use App\Services\Contracts\PaymentGateway;
use Illuminate\Support\Facades\Http;

/**
 * LINE Pay v3 線上收款（本平台唯一線上付款渠道）。
 * 需設定 channel id/secret 與 confirm/cancel 導回網址。
 */
class LinePayGateway implements PaymentGateway
{
    public function __construct(
        private string $channelId,
        private string $channelSecret,
        private string $baseUrl,
        private string $confirmUrl,
        private string $cancelUrl,
    ) {}

    public function requestPayment(Registration $registration): array
    {
        $amount = (int) round($registration->division->fee);
        $orderId = 'REG-' . $registration->id;

        $body = [
            'amount' => $amount,
            'currency' => 'TWD',
            'orderId' => $orderId,
            'packages' => [[
                'id' => $orderId,
                'amount' => $amount,
                'products' => [[
                    'name' => $registration->division->tournament->name . ' 報名',
                    'quantity' => 1,
                    'price' => $amount,
                ]],
            ]],
            'redirectUrls' => ['confirmUrl' => $this->confirmUrl, 'cancelUrl' => $this->cancelUrl],
        ];

        $res = $this->signedPost('/v3/payments/request', $body);

        return [
            'provider' => 'linepay',
            'payment_url' => data_get($res, 'info.paymentUrl.web'),
            'transaction_id' => (string) data_get($res, 'info.transactionId'),
        ];
    }

    public function confirmPayment(Registration $registration, array $callback): string
    {
        $transactionId = $callback['transaction_id'];
        $amount = (int) round($registration->division->fee);
        $this->signedPost("/v3/payments/{$transactionId}/confirm", ['amount' => $amount, 'currency' => 'TWD']);

        return (string) $transactionId;
    }

    public function refund(Registration $registration): string
    {
        $transactionId = $registration->payment_ref;
        $amount = (int) round($registration->division->fee);
        $this->signedPost("/v3/payments/{$transactionId}/refund", ['refundAmount' => $amount]);

        return 'REFUND-' . $transactionId;
    }

    private function signedPost(string $path, array $body): array
    {
        // LINE Pay 簽章：HMAC-SHA256(channelSecret + uri + body + nonce)
        $nonce = (string) \Illuminate\Support\Str::uuid();
        $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $signature = base64_encode(hash_hmac('sha256', $this->channelSecret . $path . $json . $nonce, $this->channelSecret, true));

        $res = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-LINE-ChannelId' => $this->channelId,
            'X-LINE-Authorization-Nonce' => $nonce,
            'X-LINE-Authorization' => $signature,
        ])->withBody($json, 'application/json')->post($this->baseUrl . $path);

        $res->throw();

        return $res->json();
    }
}
