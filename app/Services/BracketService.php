<?php

namespace App\Services;

use App\Enums\BattleStatus;
use App\Models\Battle;
use App\Models\Stage;
use Illuminate\Support\Collection;

/**
 * 賽程編排引擎。
 *
 * 支援循環賽（圓桌法、自動輪空）與單敗淘汰（種子排序、補輪空）。
 * 產生的對戰會偵測「同一選手同一時段不可兩場」之衝突。
 */
class BracketService
{
    /**
     * 循環賽：每位選手與其他人各對戰一次（圓桌排程，回傳每一輪的對戰）。
     *
     * @param  array<int>  $playerIds  已依種子排序的選手 id
     * @return array<int, array<array{0:?int,1:?int}>>  rounds[輪次] => [[a,b], ...]，null 代表輪空
     */
    public function roundRobinSchedule(array $playerIds): array
    {
        $players = array_values($playerIds);
        if (count($players) % 2 === 1) {
            $players[] = null; // 補一個輪空位
        }

        $n = count($players);
        $rounds = [];

        for ($r = 0; $r < $n - 1; $r++) {
            $pairs = [];
            for ($i = 0; $i < $n / 2; $i++) {
                $a = $players[$i];
                $b = $players[$n - 1 - $i];
                if ($a !== null && $b !== null) {
                    $pairs[] = [$a, $b];
                }
            }
            $rounds[] = $pairs;

            // 固定第一個，其餘順時針旋轉
            $fixed = array_shift($players);
            $last = array_pop($players);
            array_unshift($players, $last);
            array_unshift($players, $fixed);
        }

        return $rounds;
    }

    /**
     * 單敗淘汰的種子配對：標準種子（1 vs 最後、2 vs 倒數第二…），
     * 人數補到 2 的次方，多出的位置以輪空（null）填補。
     *
     * @param  array<int>  $seededPlayerIds  依強度排序（種子1 在前）
     * @return array<array{0:?int,1:?int}>  第一輪配對
     */
    public function singleEliminationFirstRound(array $seededPlayerIds): array
    {
        $count = count($seededPlayerIds);
        $size = 1;
        while ($size < $count) {
            $size <<= 1;
        }

        // 標準種子位置表
        $seedOrder = $this->seedPositions($size);

        // 補輪空：種子序之後的位置填 null
        $slots = [];
        foreach ($seedOrder as $seedIndex) {
            $slots[] = $seededPlayerIds[$seedIndex - 1] ?? null;
        }

        $pairs = [];
        for ($i = 0; $i < $size; $i += 2) {
            $pairs[] = [$slots[$i], $slots[$i + 1]];
        }

        return $pairs;
    }

    /** 產生 size（2 次方）大小的標準種子位置序列。 */
    private function seedPositions(int $size): array
    {
        $rounds = (int) log($size, 2);
        $seeds = [1];
        for ($r = 0; $r < $rounds; $r++) {
            $next = [];
            $sum = count($seeds) * 2 + 1;
            foreach ($seeds as $s) {
                $next[] = $s;
                $next[] = $sum - $s;
            }
            $seeds = $next;
        }

        return $seeds;
    }

    /**
     * 將循環賽排程落地為 Battle 紀錄，並指派場地。
     *
     * @param  array<int, array<array{0:?int,1:?int}>>  $schedule
     */
    public function persistRoundRobin(Stage $stage, array $schedule, ?int $venueId = null, ?int $groupId = null): Collection
    {
        $created = collect();
        foreach ($schedule as $pairs) {
            foreach ($pairs as [$a, $b]) {
                $created->push(Battle::create([
                    'stage_id' => $stage->id,
                    'group_id' => $groupId,
                    'venue_id' => $venueId,
                    'player_a_id' => $a,
                    'player_b_id' => $b,
                    'status' => BattleStatus::Pending,
                ]));
            }
        }

        return $created;
    }

    /**
     * 衝突偵測：同一選手是否在「未完成的對戰」中重複出現。
     *
     * @return array<int>  發生衝突的 player id
     */
    public function detectPlayerConflicts(Stage $stage): array
    {
        $counts = [];
        $battles = $stage->battles()
            ->whereIn('status', [BattleStatus::Pending->value, BattleStatus::InProgress->value])
            ->get(['player_a_id', 'player_b_id']);

        foreach ($battles as $b) {
            foreach ([$b->player_a_id, $b->player_b_id] as $pid) {
                if ($pid !== null) {
                    $counts[$pid] = ($counts[$pid] ?? 0) + 1;
                }
            }
        }

        // 一名選手同一賽段可有多場「待賽」是正常的；此處示範時段衝突的鉤子，
        // 實務上應比對 battle 的排定時間區間。回傳出賽 >1 的同時段選手。
        return array_keys(array_filter($counts, fn ($c) => $c > 1));
    }

    /**
     * 時段衝突偵測（依 scheduled_at 與固定時段長度）：
     * 同一選手或同一場地在重疊時段被排兩場。
     *
     * @return array<string>  人類可讀的衝突描述
     */
    public function detectScheduleConflicts(Stage $stage, int $slotMinutes = 15): array
    {
        $battles = $stage->battles()->whereNotNull('scheduled_at')
            ->orderBy('scheduled_at')->get();

        $conflicts = [];
        foreach ($battles as $i => $a) {
            foreach ($battles->slice($i + 1) as $b) {
                $overlap = $a->scheduled_at->diffInMinutes($b->scheduled_at) < $slotMinutes;
                if (! $overlap) {
                    continue;
                }
                if ($a->venue_id && $a->venue_id === $b->venue_id) {
                    $conflicts[] = "場地撞場：對戰 #{$a->id} 與 #{$b->id} 同場地同時段";
                }
                $aPlayers = [$a->player_a_id, $a->player_b_id];
                foreach ([$b->player_a_id, $b->player_b_id] as $pid) {
                    if ($pid && in_array($pid, $aPlayers, true)) {
                        $conflicts[] = "選手撞場：選手 #{$pid} 在對戰 #{$a->id} 與 #{$b->id} 同時段";
                    }
                }
            }
        }

        return array_values(array_unique($conflicts));
    }
}
