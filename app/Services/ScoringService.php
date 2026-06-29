<?php

namespace App\Services;

use App\Enums\BattleStatus;
use App\Enums\FinishType;
use App\Models\Battle;
use App\Models\BattleRound;
use App\Models\ScoreRule;
use Illuminate\Support\Facades\DB;

/**
 * 計分引擎 — Beyblade X 點數制（先達 N 點獲勝）。
 *
 * 每一回合（round）由裁判判定 finish 類型，依組別計分規則換算點數，
 * 累計到對戰雙方比分；任一方達到 points_to_win 即判定該場勝負。
 */
class ScoringService
{
    /**
     * 登錄一回合結果並回傳更新後的對戰。
     *
     * @param  'a'|'b'  $winnerSide  獲勝方
     */
    public function recordRound(
        Battle $battle,
        string $winnerSide,
        FinishType $finish,
        int $winnerBeybladeId,
        int $loserBeybladeId,
        ?int $refereeId = null,
    ): Battle {
        return DB::transaction(function () use ($battle, $winnerSide, $finish, $winnerBeybladeId, $loserBeybladeId, $refereeId) {
            $division = $battle->stage->division;
            $points = $this->pointsFor($division->id, $finish);

            $winnerPlayerId = $winnerSide === 'a' ? $battle->player_a_id : $battle->player_b_id;

            $seq = (int) $battle->rounds()->max('sequence') + 1;

            BattleRound::create([
                'battle_id' => $battle->id,
                'sequence' => $seq,
                'winner_player_id' => $winnerPlayerId,
                'winner_beyblade_id' => $winnerBeybladeId,
                'loser_beyblade_id' => $loserBeybladeId,
                'finish_type' => $finish,
                'points' => $points,
                'is_draw' => false,
            ]);

            if ($winnerSide === 'a') {
                $battle->score_a += $points;
            } else {
                $battle->score_b += $points;
            }

            $battle->started_at ??= now();

            if (! $this->resolveIfDecided($battle, $division->points_to_win)) {
                $battle->status = BattleStatus::InProgress;
            }
            $battle->save();

            AuditService::log($refereeId, 'score.round', $battle, [
                'side' => $winnerSide,
                'finish' => $finish->value,
                'points' => $points,
                'score' => [$battle->score_a, $battle->score_b],
            ]);

            return $battle->fresh('rounds');
        });
    }

    /** 平手回合（雙方同時停止/出場）— 不計分，僅留紀錄。 */
    public function recordDraw(Battle $battle, ?int $refereeId = null): Battle
    {
        $seq = (int) $battle->rounds()->max('sequence') + 1;
        BattleRound::create([
            'battle_id' => $battle->id,
            'sequence' => $seq,
            'is_draw' => true,
            'points' => 0,
        ]);
        AuditService::log($refereeId, 'score.draw', $battle, ['sequence' => $seq]);

        return $battle->fresh('rounds');
    }

    /** 依組別計分規則取得 finish 點數，無自訂則回退官方預設。 */
    public function pointsFor(int $divisionId, FinishType $finish): int
    {
        $rule = ScoreRule::where('division_id', $divisionId)
            ->where('finish_type', $finish->value)
            ->first();

        return $rule?->points ?? $finish->points();
    }

    /** @return bool 是否已分出勝負。 */
    private function resolveIfDecided(Battle $battle, int $pointsToWin): bool
    {
        if ($battle->score_a < $pointsToWin && $battle->score_b < $pointsToWin) {
            return false;
        }

        $battle->winner_id = $battle->score_a >= $pointsToWin
            ? $battle->player_a_id
            : $battle->player_b_id;
        $battle->status = BattleStatus::Finished;
        $battle->finished_at = now();

        return true;
    }
}
