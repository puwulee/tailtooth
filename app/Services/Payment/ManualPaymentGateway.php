<?php

namespace App\Services\Payment;

use App\Models\Registration;
use App\Services\Contracts\PaymentGateway;

/** 開發/臨櫃預設：手動確認收款，不串接外部金流。 */
class ManualPaymentGateway implements PaymentGateway
{
    public function requestPayment(Registration $registration): array
    {
        return [
            'provider' => 'manual',
            'payment_url' => null,
            'transaction_id' => 'MANUAL-' . $registration->id,
        ];
    }

    public function confirmPayment(Registration $registration, array $callback): string
    {
        return $callback['transaction_id'] ?? ('MANUAL-' . $registration->id);
    }

    public function refund(Registration $registration): string
    {
        return 'MANUAL-REFUND-' . $registration->id;
    }
}
