<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * 選手排行榜 — 賽季積分累計，冠軍可展示大頭貼與成績狀況。
 */
class LeaderboardService
{
    /**
     * @return array<int, array<string,mixed>>
     */
    public function seasonRanking(int $limit = 50): array
    {
        return DB::table('ranking_points as rp')
            ->join('players as p', 'p.id', '=', 'rp.player_id')
            ->select(
                'p.id as player_id',
                'p.nickname',
                'p.real_name',
                'p.avatar_path',
                DB::raw('sum(rp.points) as total_points'),
                DB::raw('count(rp.id) as events'),
                DB::raw('min(rp.final_rank) as best_rank'),
            )
            ->groupBy('p.id', 'p.nickname', 'p.real_name', 'p.avatar_path')
            ->orderByDesc('total_points')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }
}
