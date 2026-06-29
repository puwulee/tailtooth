<?php

namespace App\Http\Controllers;

use App\Enums\BattleStatus;
use App\Enums\FinishType;
use App\Models\Battle;
use App\Services\ScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

/**
 * 裁判計分 API（供 SPA 呼叫）。支援冪等的 client_event_id 以利離線補送不重複計分。
 */
class RefereeScoringController extends Controller
{
    public function __construct(private ScoringService $scoring) {}

    /** 對戰目前狀態（含每回合）。 */
    public function show(Battle $battle): JsonResponse
    {
        return response()->json($this->present($battle->load('rounds')));
    }

    public function start(Battle $battle): JsonResponse
    {
        if ($battle->status === BattleStatus::Pending) {
            $battle->update(['status' => BattleStatus::InProgress, 'started_at' => now()]);
        }

        return response()->json($this->present($battle->fresh('rounds')));
    }

    /** 登錄一回合（finish 點數制）。 */
    public function recordRound(Request $request, Battle $battle): JsonResponse
    {
        $data = $request->validate([
            'winner_side' => ['required', 'in:a,b'],
            'finish' => ['required', new Enum(FinishType::class)],
            'winner_beyblade_id' => ['required', 'integer'],
            'loser_beyblade_id' => ['required', 'integer'],
            'referee_id' => ['nullable', 'integer'],
            'client_event_id' => ['nullable', 'string', 'max:64'],
        ]);

        $finish = FinishType::from($data['finish']);

        // Xtreme 僅限支援的世代
        $generation = $battle->stage->division->tournament->generation;
        if ($finish === FinishType::Xtreme && ! $generation->supportsXtreme()) {
            return response()->json(['message' => '此世代不支援 Xtreme Finish'], 422);
        }

        $battle = $this->scoring->recordRound(
            $battle,
            $data['winner_side'],
            $finish,
            (int) $data['winner_beyblade_id'],
            (int) $data['loser_beyblade_id'],
            $data['referee_id'] ?? null,
            $data['client_event_id'] ?? null,
        );

        return response()->json($this->present($battle->load('rounds')));
    }

    public function recordDraw(Request $request, Battle $battle): JsonResponse
    {
        $battle = $this->scoring->recordDraw(
            $battle,
            $request->integer('referee_id') ?: null,
            $request->string('client_event_id')->toString() ?: null,
        );

        return response()->json($this->present($battle->load('rounds')));
    }

    private function present(Battle $battle): array
    {
        return [
            'id' => $battle->id,
            'status' => $battle->status->value,
            'score_a' => $battle->score_a,
            'score_b' => $battle->score_b,
            'winner_id' => $battle->winner_id,
            'points_to_win' => $battle->stage->division->points_to_win,
            'stream_url' => $battle->stream_url,
            'rounds' => $battle->rounds->map(fn ($r) => [
                'sequence' => $r->sequence,
                'finish' => $r->finish_type?->value,
                'points' => $r->points,
                'is_draw' => $r->is_draw,
                'winner_player_id' => $r->winner_player_id,
            ]),
        ];
    }
}
