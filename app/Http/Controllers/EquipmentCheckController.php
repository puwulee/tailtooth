<?php

namespace App\Http\Controllers;

use App\Enums\RegistrationStatus;
use App\Models\Division;
use App\Models\EquipmentCheck;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 裝備驗規（Beyblade Check）：裁判逐顆檢查選手陀螺是否合規，不通過需更換才可出戰。
 */
class EquipmentCheckController extends Controller
{
    public function ui(Division $division)
    {
        return view('equipment-check', ['divisionId' => $division->id, 'name' => $division->tournament->name . ' · ' . $division->name]);
    }

    /** 待驗規名單：已報到選手的陀螺與目前驗規狀態。 */
    public function data(Division $division): JsonResponse
    {
        $regs = $division->registrations()
            ->whereIn('status', [RegistrationStatus::Paid->value, RegistrationStatus::CheckedIn->value])
            ->with('player.beyblades')->get();

        $checks = EquipmentCheck::whereIn('registration_id', $regs->pluck('id'))->get()
            ->keyBy(fn ($c) => $c->registration_id . '-' . $c->beyblade_id);

        return response()->json($regs->map(fn ($r) => [
            'registration_id' => $r->id,
            'player' => $r->player?->nickname ?: $r->player?->real_name,
            'beyblades' => $r->player->beyblades->map(fn ($b) => [
                'id' => $b->id, 'name' => $b->name,
                'authenticity' => $b->authenticity->label(),
                'passed' => $checks->get($r->id . '-' . $b->id)?->passed,
            ]),
        ]));
    }

    /** 登錄驗規結果。 */
    public function record(Request $request): JsonResponse
    {
        $data = $request->validate([
            'registration_id' => ['required', 'integer', 'exists:registrations,id'],
            'beyblade_id' => ['required', 'integer', 'exists:beyblades,id'],
            'passed' => ['required', 'boolean'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        $check = EquipmentCheck::updateOrCreate(
            ['registration_id' => $data['registration_id'], 'beyblade_id' => $data['beyblade_id']],
            ['passed' => $data['passed'], 'note' => $data['note'] ?? null, 'referee_id' => $request->user()->id],
        );
        AuditService::log($request->user()->id, 'equipment.check', $check, ['passed' => $data['passed']]);

        return response()->json(['ok' => true, 'passed' => $check->passed]);
    }
}
