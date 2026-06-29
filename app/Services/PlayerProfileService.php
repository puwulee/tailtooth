<?php

namespace App\Services;

use App\Models\Player;
use App\Models\RankingPoint;
use Illuminate\Support\Facades\DB;

/**
 * 選手公開檔案：彙整跨陀螺戰績、賽季積分、最佳名次與成就徽章。
 */
class PlayerProfileService
{
    public function profile(Player $player): array
    {
        $beybladeIds = $player->beyblades()->pluck('id');

        $wins = DB::table('battle_rounds')->whereIn('winner_beyblade_id', $beybladeIds)->where('is_draw', false);
        $losses = DB::table('battle_rounds')->whereIn('loser_beyblade_id', $beybladeIds)->where('is_draw', false);
        $winCount = (clone $wins)->count();
        $lossCount = (clone $losses)->count();
        $appearances = $winCount + $lossCount;

        $finishes = (clone $wins)->select('finish_type', DB::raw('count(*) as c'))
            ->groupBy('finish_type')->pluck('c', 'finish_type')->toArray();

        $ranks = RankingPoint::where('player_id', $player->id)->get();
        $seasonPoints = (int) $ranks->sum('points');
        $bestRank = $ranks->min('final_rank');

        return [
            'player' => [
                'id' => $player->id,
                'name' => $player->nickname ?: $player->real_name,
                'avatar' => $player->avatar_path ? asset('storage/' . $player->avatar_path) : null,
            ],
            'season_points' => $seasonPoints,
            'best_rank' => $bestRank,
            'events' => $ranks->count(),
            'wins' => $winCount,
            'losses' => $lossCount,
            'win_rate' => $appearances ? round($winCount / $appearances, 3) : 0.0,
            'finishes' => $finishes,
            'beyblades' => $player->beyblades->map(fn ($b) => ['name' => $b->name, 'generation' => $b->generation->label()]),
            'badges' => $this->badges($bestRank, $ranks->count(), $appearances, $finishes),
        ];
    }

    /** 依成績推導成就徽章。 */
    private function badges(?int $bestRank, int $events, int $appearances, array $finishes): array
    {
        $badges = [];
        if ($bestRank === 1) {
            $badges[] = ['icon' => '🏆', 'label' => '冠軍'];
        } elseif ($bestRank !== null && $bestRank <= 4) {
            $badges[] = ['icon' => '🥉', 'label' => '四強'];
        }
        if ($events >= 3) {
            $badges[] = ['icon' => '🎖️', 'label' => '常勝參賽者'];
        }
        if ($appearances >= 20) {
            $badges[] = ['icon' => '⚔️', 'label' => '百戰老兵'];
        }
        if (($finishes['xtreme'] ?? 0) >= 3) {
            $badges[] = ['icon' => '🌟', 'label' => '極限大師'];
        }
        if (($finishes['burst'] ?? 0) >= 5) {
            $badges[] = ['icon' => '💥', 'label' => '爆裂王'];
        }

        return $badges;
    }
}
