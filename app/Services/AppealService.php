<?php

namespace App\Services;

use App\Enums\BattleStatus;
use App\Models\Appeal;
use App\Models\Battle;
use App\Models\Player;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * 申訴審理：選手對對戰結果提出申訴 → 主審/主辦裁決（成立則重判，駁回則維持）。
 */
class AppealService
{
    /** 提出申訴（限該場結束後、且尚未確認）。 */
    public function file(Battle $battle, Player $player, string $reason): Appeal
    {
        if (! in_array($battle->status, [BattleStatus::Finished, BattleStatus::Appealed], true)) {
            throw ValidationException::withMessages(['battle' => '此對戰目前不可申訴']);
        }
        if (! in_array($player->id, [$battle->player_a_id, $battle->player_b_id], true)) {
            throw ValidationException::withMessages(['player' => '僅參賽選手可申訴']);
        }

        return DB::transaction(function () use ($battle, $player, $reason) {
            $appeal = Appeal::create([
                'battle_id' => $battle->id,
                'player_id' => $player->id,
                'reason' => $reason,
                'status' => 'open',
            ]);
            $battle->update(['status' => BattleStatus::Appealed]);
            AuditService::log($player->user_id, 'appeal.file', $appeal, ['battle_id' => $battle->id]);

            return $appeal;
        });
    }

    /** 裁決：uphold（成立→重判，比分歸零回進行中）或 reject（駁回→維持結果）。 */
    public function rule(Appeal $appeal, int $ruledBy, string $decision, ?string $ruling = null): Appeal
    {
        if (! in_array($decision, ['uphold', 'reject'], true)) {
            throw ValidationException::withMessages(['decision' => '裁決結果無效']);
        }

        return DB::transaction(function () use ($appeal, $ruledBy, $decision, $ruling) {
            $battle = $appeal->battle;

            if ($decision === 'uphold') {
                $battle->update([
                    'status' => BattleStatus::InProgress,
                    'score_a' => 0, 'score_b' => 0, 'winner_id' => null, 'finished_at' => null,
                ]);
                $battle->rounds()->delete();
            } else {
                $battle->update(['status' => BattleStatus::Confirmed]);
            }

            $appeal->update([
                'status' => $decision === 'uphold' ? 'upheld' : 'rejected',
                'ruled_by' => $ruledBy,
                'ruling' => $ruling,
            ]);
            AuditService::log($ruledBy, 'appeal.rule', $appeal, ['decision' => $decision]);

            return $appeal->fresh();
        });
    }
}
