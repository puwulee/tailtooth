<?php

namespace App\Services;

use App\Enums\BattleStatus;
use App\Models\Battle;
use App\Models\Tournament;

/**
 * 賽事電子檔案：彙整賽事結果為結構化資料（可再轉 PDF/圖文/公告投放）。
 */
class ArchiveService
{
    /** 產生賽事成績電子檔案資料。 */
    public function tournamentResults(Tournament $tournament): array
    {
        $battles = Battle::query()
            ->whereHas('stage.division', fn ($q) => $q->where('tournament_id', $tournament->id))
            ->whereIn('status', [BattleStatus::Finished->value, BattleStatus::Confirmed->value])
            ->with(['playerA', 'playerB', 'winner', 'stage.division'])
            ->get();

        $divisions = $battles->groupBy(fn ($b) => $b->stage?->division?->name ?? '—')
            ->map(fn ($group, $name) => [
                'division' => $name,
                'battles' => $group->map(fn ($b) => [
                    'a' => $b->playerA?->nickname ?: $b->playerA?->real_name,
                    'b' => $b->playerB?->nickname ?: $b->playerB?->real_name,
                    'score' => "{$b->score_a}:{$b->score_b}",
                    'winner' => $b->winner?->nickname ?: $b->winner?->real_name,
                ])->values(),
            ])->values();

        return [
            'tournament' => $tournament->name,
            'event_date' => $tournament->event_date?->toDateString(),
            'generated_at' => now()->toDateTimeString(),
            'total_battles' => $battles->count(),
            'divisions' => $divisions,
            'combo_leaderboard' => app(BeybladeStatsService::class)->comboLeaderboard(10),
            'season_ranking' => app(LeaderboardService::class)->seasonRanking(10),
        ];
    }

    /** 簡易 HTML 公告版本（可直接貼到官網/轉 PDF）。 */
    public function toHtml(array $results): string
    {
        $rows = '';
        foreach ($results['divisions'] as $div) {
            $rows .= '<h2>' . e($div['division']) . '</h2><ul>';
            foreach ($div['battles'] as $b) {
                $rows .= '<li>' . e($b['a']) . ' ' . e($b['score']) . ' ' . e($b['b'])
                    . '　🏆 ' . e($b['winner']) . '</li>';
            }
            $rows .= '</ul>';
        }

        return "<!doctype html><meta charset='utf-8'><title>{$results['tournament']} 成績</title>"
            . "<h1>{$results['tournament']} 成績公告</h1>"
            . "<p>賽事日期：{$results['event_date']}　產生時間：{$results['generated_at']}</p>"
            . $rows;
    }
}
