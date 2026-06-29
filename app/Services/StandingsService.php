<?php

namespace App\Services;

use App\Enums\BattleStatus;
use App\Models\Battle;
use App\Models\Player;
use App\Models\RankingPoint;
use App\Models\Stage;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;

/**
 * 名次與積分結算：循環賽排名、淘汰賽勝者自動晉級、賽季積分寫入。
 */
class StandingsService
{
    /** 賽事名次 → 賽季積分對照（可調）。 */
    public const RANK_POINTS = [1 => 100, 2 => 70, 3 => 50, 4 => 35, 5 => 25, 6 => 18, 7 => 12, 8 => 8];

    /**
     * 計算某賽段的循環賽排名（勝場優先、再比得分差）。
     *
     * @return array<int, array<string,mixed>> 依名次排序
     */
    public function roundRobinStandings(Stage $stage): array
    {
        $battles = $stage->battles()
            ->whereIn('status', [BattleStatus::Finished->value, BattleStatus::Confirmed->value])
            ->get();

        $table = [];
        $touch = function (&$table, $pid) {
            $table[$pid] ??= ['player_id' => $pid, 'wins' => 0, 'losses' => 0, 'pf' => 0, 'pa' => 0];
        };

        foreach ($battles as $b) {
            if (! $b->player_a_id || ! $b->player_b_id) {
                continue;
            }
            $touch($table, $b->player_a_id);
            $touch($table, $b->player_b_id);
            $table[$b->player_a_id]['pf'] += $b->score_a;
            $table[$b->player_a_id]['pa'] += $b->score_b;
            $table[$b->player_b_id]['pf'] += $b->score_b;
            $table[$b->player_b_id]['pa'] += $b->score_a;
            if ($b->winner_id === $b->player_a_id) {
                $table[$b->player_a_id]['wins']++;
                $table[$b->player_b_id]['losses']++;
            } elseif ($b->winner_id === $b->player_b_id) {
                $table[$b->player_b_id]['wins']++;
                $table[$b->player_a_id]['losses']++;
            }
        }

        $rows = array_values($table);
        usort($rows, fn ($a, $b) => [$b['wins'], $b['pf'] - $b['pa']] <=> [$a['wins'], $a['pf'] - $a['pa']]);

        foreach ($rows as $i => &$row) {
            $row['rank'] = $i + 1;
            $row['player'] = Player::find($row['player_id'])?->only(['nickname', 'real_name']);
        }

        return $rows;
    }

    /**
     * 淘汰賽：某場確認勝負後，將勝者送往 next_battle_id 的指定位置。
     */
    public function advanceWinner(Battle $battle): void
    {
        if (! $battle->next_battle_id || ! $battle->winner_id) {
            return;
        }
        $next = Battle::find($battle->next_battle_id);
        if (! $next) {
            return;
        }
        $col = $battle->next_slot === 1 ? 'player_b_id' : 'player_a_id';
        $next->update([$col => $battle->winner_id]);
    }

    /**
     * 結算賽段名次 → 寫入賽季積分（updateOrCreate 可重複結算）。
     */
    public function finalizeStage(Stage $stage): array
    {
        $division = $stage->division;
        $tournament = $division->tournament;
        $standings = $this->roundRobinStandings($stage);

        return DB::transaction(function () use ($standings, $tournament, $division) {
            foreach ($standings as $row) {
                RankingPoint::updateOrCreate(
                    ['player_id' => $row['player_id'], 'tournament_id' => $tournament->id, 'division_id' => $division->id],
                    ['final_rank' => $row['rank'], 'points' => self::RANK_POINTS[$row['rank']] ?? 5],
                );
            }
            AuditService::log(null, 'standings.finalize', $division, ['players' => count($standings)]);

            return $standings;
        });
    }

    /** 將整個賽事標記結束。 */
    public function finalizeTournament(Tournament $tournament): void
    {
        $tournament->update(['status' => 'finished']);
        AuditService::log(null, 'tournament.finalize', $tournament, []);
    }
}
