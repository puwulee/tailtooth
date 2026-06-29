<?php

namespace App\Services;

use App\Models\Division;
use App\Models\ScoreRule;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;

/**
 * 賽事影印：把既有賽事（或範本）複製成一場新活動，
 * 連同組別與計分規則一併帶過，方便快速開賽、推廣優惠。
 */
class TournamentService
{
    /**
     * @param  array<string,mixed>  $overrides  覆寫新賽事欄位（如 name、event_date、promo）
     */
    public function cloneTournament(Tournament $source, array $overrides = [], ?int $actorId = null): Tournament
    {
        return DB::transaction(function () use ($source, $overrides, $actorId) {
            $new = $source->replicate(['cloned_from_id', 'is_template', 'created_at', 'updated_at']);
            $new->cloned_from_id = $source->id;
            $new->is_template = false;
            $new->status = 'draft';
            $new->name = $overrides['name'] ?? ($source->name . '（複製）');
            $new->fill($overrides);
            $new->save();

            foreach ($source->divisions as $division) {
                /** @var Division $newDivision */
                $newDivision = $division->replicate(['tournament_id', 'created_at', 'updated_at']);
                $newDivision->tournament_id = $new->id;
                $newDivision->save();

                foreach ($division->scoreRules as $rule) {
                    /** @var ScoreRule $newRule */
                    $newRule = $rule->replicate(['division_id', 'created_at', 'updated_at']);
                    $newRule->division_id = $newDivision->id;
                    $newRule->save();
                }
            }

            AuditService::log($actorId, 'tournament.clone', $new, ['source_id' => $source->id]);

            return $new->fresh('divisions');
        });
    }
}
