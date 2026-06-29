<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Models\Tournament;
use App\Services\RegistrationService;
use Illuminate\Http\JsonResponse;

/** 後台報名管理：名單、報到、退費。 */
class RegistrationAdminController extends Controller
{
    public function __construct(private RegistrationService $svc) {}

    public function ui(Tournament $tournament)
    {
        return view('admin-registrations', ['tournamentId' => $tournament->id, 'name' => $tournament->name]);
    }

    public function index(Tournament $tournament): JsonResponse
    {
        $rows = Registration::whereHas('division', fn ($q) => $q->where('tournament_id', $tournament->id))
            ->with(['player', 'division'])->latest()->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'player' => $r->player?->nickname ?: $r->player?->real_name,
                'division' => $r->division?->name,
                'status' => $r->status->value,
                'invoice' => $r->invoice_number,
                'paid_at' => $r->paid_at?->format('Y-m-d H:i'),
            ]);

        return response()->json($rows);
    }

    public function checkIn(Registration $registration): JsonResponse
    {
        return response()->json(['status' => $this->svc->checkIn($registration)->status->value]);
    }

    public function refund(Registration $registration): JsonResponse
    {
        return response()->json(['status' => $this->svc->refund($registration)->status->value]);
    }
}
