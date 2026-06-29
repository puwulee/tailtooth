<?php

namespace App\Services\Contracts;

use App\Models\Registration;

interface InvoiceIssuer
{
    /** 開立電子發票，回傳發票號碼。 */
    public function issue(Registration $registration): string;

    /** 開立折讓單（退費時沖銷發票）。 */
    public function allowance(Registration $registration): string;
}
