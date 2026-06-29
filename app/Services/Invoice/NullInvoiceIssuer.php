<?php

namespace App\Services\Invoice;

use App\Models\Registration;
use App\Services\Contracts\InvoiceIssuer;

/** 開發預設：產生模擬發票號碼，不串接外部。 */
class NullInvoiceIssuer implements InvoiceIssuer
{
    public function issue(Registration $registration): string
    {
        return 'INV-' . str_pad((string) $registration->id, 8, '0', STR_PAD_LEFT);
    }

    public function allowance(Registration $registration): string
    {
        return 'ALW-' . str_pad((string) $registration->id, 8, '0', STR_PAD_LEFT);
    }
}
