<?php

namespace App\Http\Controllers;

use App\Services\RegistrationService;
use Illuminate\Http\Request;

/** QR 報到：工作人員掃描選手 QR（內含 token）即報到。 */
class CheckInController extends Controller
{
    public function scan(Request $request, string $token, RegistrationService $svc)
    {
        try {
            $reg = $svc->checkInByToken($token);

            return response()->json(['ok' => true, 'player' => $reg->player?->nickname ?: $reg->player?->real_name, 'status' => $reg->status->value]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => '無效的報到碼'], 404);
        }
    }
}
