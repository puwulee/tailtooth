<?php

namespace App\Http\Controllers;

use App\Enums\BattleStatus;
use App\Enums\RegistrationStatus;
use App\Models\Battle;
use App\Models\Division;
use App\Models\Stage;
use App\Models\Tournament;
use App\Models\Venue;
use App\Services\AuditService;
use App\Services\BracketService;
use App\Services\StandingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 賽程編排後台：產生循環賽/單敗淘汰賽程、指派與抽選場地、衝突偵測、名次積分結算。
 */
class SchedulingController extends Controller
{
    public function __construct(
        private BracketService $bracket,
        private StandingsService $standings,
    ) {}

    /** 某賽段循環賽排名。 */
    public function standings(Stage $stage): JsonResponse
    {
        return response()->json($this->standings->roundRobinStandings($stage));
    }

    /** 結算組別名次 → 寫入賽季積分。 */
    public function finalize(Division $division): JsonResponse
    {
        $result = [];
        foreach ($division->stages as $stage) {
            $result = $this->standings->finalizeStage($stage);
        }

        return response()->json(['standings' => $result]);
    }

    /** 編排後台 UI。 */
    public function ui(Tournament $tournament)
    {
        return view('scheduling', ['tournamentId' => $tournament->id, 'name' => $tournament->name]);
    }

    /** 賽事的組別 + 各組已繳費選手 + 可用場地。 */
    public function overview(Tournament $tournament): JsonResponse
    {
        $divisions = $tournament->divisions()->withCount(['registrations as ready_count' => function ($q) {
            $q->whereIn('status', [RegistrationStatus::Paid->value, RegistrationStatus::CheckedIn->value]);
        }])->get()->map(fn ($d) => [
            'id' => $d->id,
            'name' => $d->name,
            'format_default' => $d->deck_mode->value,
            'ready_players' => $d->ready_count,
            'stages' => $d->stages()->withCount('battles')->get()
                ->map(fn ($s) => ['id' => $s->id, 'type' => $s->type->value, 'format' => $s->format->value, 'battles' => $s->battles_count]),
        ]);

        return response()->json([
            'tournament' => $tournament->name,
            'divisions' => $divisions,
            'venues' => Venue::where('approval_status', 'approved')->orWhere('approval_status', 'pending')
                ->get(['id', 'name'])->map(fn ($v) => ['id' => $v->id, 'name' => $v->name]),
        ]);
    }

    /** 產生賽程：round_robin 或 single_elim。依賽季積分排種。 */
    public function generate(Request $request, Division $division): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:group,playoff,final'],
            'format' => ['required', 'in:round_robin,single_elim'],
            'venue_id' => ['nullable', 'integer', 'exists:venues,id'],
        ]);

        $playerIds = $this->seededPlayerIds($division);
        if (count($playerIds) < 2) {
            return response()->json(['message' => '可出戰選手不足 2 人'], 422);
        }

        return DB::transaction(function () use ($division, $data, $playerIds) {
            $sequence = (int) $division->stages()->max('sequence') + 1;
            $stage = Stage::create([
                'division_id' => $division->id,
                'type' => $data['type'],
                'format' => $data['format'],
                'sequence' => $sequence,
            ]);

            if ($data['format'] === 'round_robin') {
                $schedule = $this->bracket->roundRobinSchedule($playerIds);
                $battles = $this->bracket->persistRoundRobin($stage, $schedule, $data['venue_id'] ?? null);
            } else {
                $pairs = $this->bracket->singleEliminationFirstRound($playerIds);
                $battles = collect();
                foreach ($pairs as $slot => [$a, $b]) {
                    $battles->push(Battle::create([
                        'stage_id' => $stage->id,
                        'venue_id' => $data['venue_id'] ?? null,
                        'player_a_id' => $a,
                        'player_b_id' => $b,
                        'bracket_slot' => $slot,
                        // 一方為輪空(null) → 另一方直接晉級
                        'status' => ($a === null xor $b === null) ? BattleStatus::Finished : BattleStatus::Pending,
                        'winner_id' => $a === null ? $b : ($b === null ? $a : null),
                    ]));
                }
            }

            AuditService::log(null, 'schedule.generate', $stage, [
                'format' => $data['format'], 'players' => count($playerIds), 'battles' => $battles->count(),
            ]);

            return response()->json([
                'stage_id' => $stage->id,
                'battles' => $battles->count(),
                'conflicts' => $this->bracket->detectPlayerConflicts($stage),
            ], 201);
        });
    }

    /** 抽選場地（複賽/冠軍戰），記錄抽選人與時間以示公正。 */
    public function drawVenue(Request $request, Battle $battle): JsonResponse
    {
        $data = $request->validate(['venue_ids' => ['required', 'array', 'min:1'], 'venue_ids.*' => ['integer', 'exists:venues,id']]);

        // 以對戰 id 為種子做可重現的抽選（避免 Math.random 不可重現）
        $ids = $data['venue_ids'];
        $picked = $ids[$battle->id % count($ids)];

        $battle->update(['venue_id' => $picked, 'venue_drawn' => true, 'venue_drawn_by' => $request->integer('actor_id') ?: null]);
        AuditService::log($request->integer('actor_id') ?: null, 'venue.draw', $battle, ['pool' => $ids, 'picked' => $picked]);

        return response()->json(['venue_id' => $picked]);
    }

    /** 某組別的賽程（依賽段分組）+ 衝突。 */
    public function battles(Division $division): JsonResponse
    {
        $stages = $division->stages()->with(['battles.playerA', 'battles.playerB', 'battles.venue'])->orderBy('sequence')->get();

        return response()->json($stages->map(fn ($s) => [
            'stage_id' => $s->id,
            'type' => $s->type->value,
            'format' => $s->format->value,
            'conflicts' => $this->bracket->detectPlayerConflicts($s),
            'battles' => $s->battles->map(fn ($b) => [
                'id' => $b->id,
                'a' => $b->playerA?->nickname ?: $b->playerA?->real_name,
                'b' => $b->playerB?->nickname ?: $b->playerB?->real_name,
                'venue' => $b->venue?->name,
                'venue_drawn' => $b->venue_drawn,
                'status' => $b->status->value,
            ]),
        ]));
    }

    /** 取得可出戰選手 id，依賽季積分排種（強者在前）。 */
    private function seededPlayerIds(Division $division): array
    {
        return $division->registrations()
            ->whereIn('status', [RegistrationStatus::Paid->value, RegistrationStatus::CheckedIn->value])
            ->join('players', 'players.id', '=', 'registrations.player_id')
            ->leftJoin('ranking_points', 'ranking_points.player_id', '=', 'players.id')
            ->groupBy('players.id')
            ->orderByRaw('COALESCE(SUM(ranking_points.points),0) DESC')
            ->pluck('players.id')
            ->all();
    }
}
