<?php

namespace App\Services\Contracts;

use App\Models\Registration;

interface PaymentGateway
{
    /** 發起付款，回傳給前端導向的付款資訊（如 LINE Pay paymentUrl + transactionId）。 */
    public function requestPayment(Registration $registration): array;

    /** 確認付款（收到回呼後），回傳交易序號。 */
    public function confirmPayment(Registration $registration, array $callback): string;

    /** 退款，回傳退款交易序號。 */
    public function refund(Registration $registration): string;
}
