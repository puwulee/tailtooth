<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Player;
use App\Services\RegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    /** 我的陀螺清單（含照片處理狀態）。 */
    public function beyblades(Request $request): JsonResponse
    {
        $player = $request->user()->player;
        $list = $player ? $player->beyblades()->latest()->get()->map(fn ($b) => [
            'id' => $b->id, 'name' => $b->name,
            'generation' => $b->generation->label(),
            'authenticity' => $b->authenticity->label(),
            'photo_status' => $b->photo_status,
            'image' => $b->watermarked_path ? asset('storage/' . $b->watermarked_path) : null,
        ]) : [];

        return response()->json($list);
    }

    /** 我的報名紀錄。 */
    public function registrations(Request $request): JsonResponse
    {
        $player = $request->user()->player;
        $list = $player ? $player->registrations()->with('division.tournament')->latest()->get()->map(fn ($r) => [
            'tournament' => $r->division->tournament->name,
            'division' => $r->division->name,
            'status' => $r->status->value,
            'invoice' => $r->invoice_number,
        ]) : [];

        return response()->json($list);
    }

    /** 送出報名（建立/更新選手資料 → 報名 → 取得付款資訊）。 */
    public function register(Request $request, Division $division, RegistrationService $svc): JsonResponse
    {
        $data = $request->validate([
            'real_name' => ['required', 'string', 'max:60'],
            'nickname' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
            'birthdate' => ['nullable', 'date'],
            'guardian_name' => ['nullable', 'string', 'max:60'],
            'guardian_phone' => ['nullable', 'string', 'max:30'],
            'guardian_consent' => ['nullable', 'boolean'],
            'portrait_consent' => ['nullable', 'boolean'],
            'invoice_carrier' => ['nullable', 'string', 'max:30'],
        ]);

        $user = $request->user();
        $player = Player::updateOrCreate(['user_id' => $user->id], [
            'real_name' => $data['real_name'], 'nickname' => $data['nickname'] ?? null,
            'phone' => $data['phone'] ?? null, 'birthdate' => $data['birthdate'] ?? null,
            'guardian_name' => $data['guardian_name'] ?? null, 'guardian_phone' => $data['guardian_phone'] ?? null,
            'guardian_consent' => (bool) ($data['guardian_consent'] ?? false),
            'portrait_consent' => (bool) ($data['portrait_consent'] ?? false),
        ]);

        $registration = $svc->register($division, $player, $data['invoice_carrier'] ?? null);
        $payment = $svc->startPayment($registration);

        return response()->json(['registration_id' => $registration->id, 'status' => $registration->status->value, 'payment' => $payment], 201);
    }
}
