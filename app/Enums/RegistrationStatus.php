<?php

namespace App\Enums;

enum RegistrationStatus: string
{
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case CheckedIn = 'checked_in';
    case Withdrawn = 'withdrawn';
    case Refunded = 'refunded';
    case Waitlisted = 'waitlisted';
}
