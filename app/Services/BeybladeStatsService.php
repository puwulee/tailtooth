<?php

namespace App\Services;

use App\Models\Beyblade;
use Illuminate\Support\Facades\DB;

/**
 * 陀螺戰績統計 — 本平台特色（與官方不同）：
 * 統計「每一顆陀螺」與「每一種陀螺組合(combo)」的勝率、得分與 finish 分布。
 */
class BeybladeStatsService
{
    /** 單顆陀螺的戰績。 */
    public function forBeyblade(int $beybladeId): array
    {
        $won = DB::table('battle_rounds')->where('winner_beyblade_id', $beybladeId)->where('is_draw', false);
        $lost = DB::table('battle_rounds')->where('loser_beyblade_id', $beybladeId)->where('is_draw', false);

        $wins = (clone $won)->count();
        $losses = (clone $lost)->count();
        $appearances = $wins + $losses;

        $finishBreakdown = (clone $won)
            ->select('finish_type', DB::raw('count(*) as c'))
            ->groupBy('finish_type')
            ->pluck('c', 'finish_type')
            ->toArray();

        return [
            'beyblade_id' => $beybladeId,
            'appearances' => $appearances,
            'wins' => $wins,
            'losses' => $losses,
            'win_rate' => $appearances ? round($wins / $appearances, 4) : 0.0,
            'points_scored' => (int) (clone $won)->sum('points'),
            'finish_breakdown' => $finishBreakdown,
        ];
    }

    /**
     * 陀螺組合(combo)排行榜：跨選手聚合相同 combo_signature 的回合勝率與得分。
     *
     * @return array<int, array<string,mixed>>
     */
    public function comboLeaderboard(int $limit = 20): array
    {
        // 勝方回合（join 取得 combo 與正版性、世代）
        $wins = DB::table('battle_rounds as r')
            ->join('beyblades as b', 'b.id', '=', 'r.winner_beyblade_id')
            ->where('r.is_draw', false)
            ->whereNotNull('b.combo_signature')
            ->select('b.combo_signature', 'b.generation', 'b.authenticity',
                DB::raw('count(*) as wins'), DB::raw('sum(r.points) as points'))
            ->groupBy('b.combo_signature', 'b.generation', 'b.authenticity')
            ->get()
            ->keyBy('combo_signature');

        $losses = DB::table('battle_rounds as r')
            ->join('beyblades as b', 'b.id', '=', 'r.loser_beyblade_id')
            ->where('r.is_draw', false)
            ->whereNotNull('b.combo_signature')
            ->select('b.combo_signature', DB::raw('count(*) as losses'))
            ->groupBy('b.combo_signature')
            ->pluck('losses', 'combo_signature');

        $rows = [];
        foreach ($wins as $sig => $w) {
            $l = (int) ($losses[$sig] ?? 0);
            $appearances = (int) $w->wins + $l;
            $rows[] = [
                'combo_signature' => $sig,
                'generation' => $w->generation,
                'authenticity' => $w->authenticity,
                'appearances' => $appearances,
                'wins' => (int) $w->wins,
                'losses' => $l,
                'win_rate' => $appearances ? round($w->wins / $appearances, 4) : 0.0,
                'points' => (int) $w->points,
            ];
        }

        // 也補上「只輸沒贏」的 combo
        foreach ($losses as $sig => $l) {
            if (! isset($wins[$sig])) {
                $bey = Beyblade::where('combo_signature', $sig)->first();
                $rows[] = [
                    'combo_signature' => $sig,
                    'generation' => $bey?->generation?->value,
                    'authenticity' => $bey?->authenticity?->value,
                    'appearances' => (int) $l,
                    'wins' => 0,
                    'losses' => (int) $l,
                    'win_rate' => 0.0,
                    'points' => 0,
                ];
            }
        }

        usort($rows, fn ($a, $b) => [$b['win_rate'], $b['points']] <=> [$a['win_rate'], $a['points']]);

        return array_slice($rows, 0, $limit);
    }
}
