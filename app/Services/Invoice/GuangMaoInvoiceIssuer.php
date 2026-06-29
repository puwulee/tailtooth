<?php

namespace App\Services\Invoice;

use App\Models\Registration;
use App\Services\Contracts\InvoiceIssuer;
use Illuminate\Support\Facades\Http;

/**
 * 光貿電子發票開立。支援手機載具/統編/捐贈碼，退費時開立折讓單。
 */
class GuangMaoInvoiceIssuer implements InvoiceIssuer
{
    public function __construct(
        private string $endpoint,
        private string $merchantId,
        private string $apiKey,
    ) {}

    public function issue(Registration $registration): string
    {
        $res = Http::asJson()->withHeaders(['Authorization' => 'Bearer ' . $this->apiKey])
            ->post($this->endpoint . '/invoice/issue', [
                'merchant_id' => $this->merchantId,
                'order_no' => 'REG-' . $registration->id,
                'amount' => (int) round($registration->division->fee),
                'carrier' => $registration->invoice_carrier, // 手機載具/統編/捐贈碼
                'item_name' => $registration->division->tournament->name . ' 報名費',
            ]);
        $res->throw();

        return (string) data_get($res->json(), 'invoice_number');
    }

    public function allowance(Registration $registration): string
    {
        $res = Http::asJson()->withHeaders(['Authorization' => 'Bearer ' . $this->apiKey])
            ->post($this->endpoint . '/invoice/allowance', [
                'merchant_id' => $this->merchantId,
                'invoice_number' => $registration->invoice_number,
                'amount' => (int) round($registration->division->fee),
            ]);
        $res->throw();

        return (string) data_get($res->json(), 'allowance_number');
    }
}
