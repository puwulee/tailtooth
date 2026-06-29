<?php

namespace App\Services;

use App\Enums\RegistrationStatus;
use App\Models\Division;
use App\Models\Player;
use App\Models\Registration;
use App\Services\Contracts\InvoiceIssuer;
use App\Services\Contracts\PaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * 報名主線：報名 → LINE Pay 收款 → 光貿電子發票；退費 → 折讓。
 * 額滿轉候補；兒童組需監護人同意。
 */
class RegistrationService
{
    public function __construct(
        private PaymentGateway $payment,
        private InvoiceIssuer $invoice,
    ) {}

    /** 建立報名（待付款），額滿則候補。 */
    public function register(Division $division, Player $player, ?string $invoiceCarrier = null): Registration
    {
        // 兒童組需監護人同意
        if ($division->age_group->value === 'kids' && ! $player->guardian_consent) {
            throw ValidationException::withMessages(['guardian_consent' => '兒童組報名需監護人同意']);
        }

        return DB::transaction(function () use ($division, $player, $invoiceCarrier) {
            $paidCount = $division->registrations()
                ->whereIn('status', [RegistrationStatus::Paid->value, RegistrationStatus::CheckedIn->value])
                ->count();

            $full = $division->capacity !== null && $paidCount >= $division->capacity;

            $registration = Registration::create([
                'division_id' => $division->id,
                'player_id' => $player->id,
                'status' => $full ? RegistrationStatus::Waitlisted : RegistrationStatus::PendingPayment,
                'invoice_carrier' => $invoiceCarrier,
            ]);

            AuditService::log($player->user_id, 'registration.create', $registration, ['waitlisted' => $full]);

            return $registration;
        });
    }

    /** 發起付款，回傳前端導向資訊。 */
    public function startPayment(Registration $registration): array
    {
        return $this->payment->requestPayment($registration);
    }

    /** 確認收款 → 標記已付款 → 開立電子發票。 */
    public function confirmPayment(Registration $registration, array $callback): Registration
    {
        return DB::transaction(function () use ($registration, $callback) {
            $txn = $this->payment->confirmPayment($registration, $callback);

            $registration->update([
                'status' => RegistrationStatus::Paid,
                'payment_ref' => $txn,
                'paid_at' => now(),
            ]);

            $invoiceNumber = $this->invoice->issue($registration);
            $registration->update(['invoice_number' => $invoiceNumber]);

            AuditService::log($registration->player->user_id, 'registration.paid', $registration, [
                'payment_ref' => $txn,
                'invoice_number' => $invoiceNumber,
            ]);

            return $registration->fresh();
        });
    }

    /** 報到。 */
    public function checkIn(Registration $registration): Registration
    {
        $registration->update([
            'status' => RegistrationStatus::CheckedIn,
            'checked_in_at' => now(),
        ]);
        AuditService::log($registration->player->user_id, 'registration.checkin', $registration);

        return $registration;
    }

    /** 退費：依退費政策決定是否全額；開立折讓單沖銷發票。 */
    public function refund(Registration $registration): Registration
    {
        return DB::transaction(function () use ($registration) {
            $daysBefore = now()->startOfDay()->diffInDays($registration->division->tournament->event_date, false);
            $fullRefund = $daysBefore >= config('billing.refund_full_days_before');

            if ($registration->payment_ref) {
                $this->payment->refund($registration);
            }
            if ($registration->invoice_number) {
                $this->invoice->allowance($registration);
            }

            $registration->update(['status' => RegistrationStatus::Refunded]);

            AuditService::log($registration->player->user_id, 'registration.refund', $registration, [
                'full_refund' => $fullRefund,
                'days_before' => $daysBefore,
            ]);

            return $registration->fresh();
        });
    }
}
