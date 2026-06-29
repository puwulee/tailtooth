<?php

namespace App\Http\Controllers;

use App\Models\Appeal;
use App\Models\Battle;
use App\Services\AppealService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppealController extends Controller
{
    public function __construct(private AppealService $appeals) {}

    /** 參賽者提出申訴。 */
    public function store(Request $request, Battle $battle): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $player = $request->user()->player;
        abort_unless($player, 403, '僅參賽者可申訴');

        $appeal = $this->appeals->file($battle, $player, $data['reason']);

        return response()->json(['id' => $appeal->id, 'status' => $appeal->status], 201);
    }

    /** 待審申訴列表（裁判/主辦）。 */
    public function index(Request $request): JsonResponse
    {
        $appeals = Appeal::with(['battle', 'player'])
            ->where('status', $request->query('status', 'open'))
            ->latest()->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'battle_id' => $a->battle_id,
                'player' => $a->player?->nickname ?: $a->player?->real_name,
                'reason' => $a->reason,
                'status' => $a->status,
            ]);

        return response()->json($appeals);
    }

    /** 裁決。 */
    public function rule(Request $request, Appeal $appeal): JsonResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:uphold,reject'],
            'ruling' => ['nullable', 'string', 'max:500'],
        ]);
        $appeal = $this->appeals->rule($appeal, $request->user()->id, $data['decision'], $data['ruling'] ?? null);

        return response()->json(['status' => $appeal->status]);
    }
}
