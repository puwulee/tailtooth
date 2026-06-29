<?php

namespace Tests\Feature;

use App\Enums\BattleStatus;
use App\Enums\FinishType;
use App\Models\Battle;
use App\Models\Beyblade;
use App\Models\Division;
use App\Models\Player;
use App\Models\Stage;
use App\Models\Tournament;
use App\Services\BracketService;
use App\Services\ScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionEngineTest extends TestCase
{
    use RefreshDatabase;

    private function makeBattle(int $pointsToWin = 4): Battle
    {
        $tournament = Tournament::create([
            'name' => 'Tailtooth Cup',
            'event_date' => '2026-08-01',
            'generation' => 'beyblade_x',
            'rule_version' => '2026.1',
        ]);
        $division = Division::create([
            'tournament_id' => $tournament->id,
            'name' => '成人組',
            'age_group' => 'adult',
            'deck_mode' => 'deck',
            'points_to_win' => $pointsToWin,
        ]);
        $stage = Stage::create([
            'division_id' => $division->id,
            'type' => 'group',
            'format' => 'round_robin',
        ]);

        $pa = Player::create(['real_name' => 'Aoi']);
        $pb = Player::create(['real_name' => 'Ren']);

        return Battle::create([
            'stage_id' => $stage->id,
            'player_a_id' => $pa->id,
            'player_b_id' => $pb->id,
        ]);
    }

    public function test_xtreme_finish_scores_three_points(): void
    {
        $this->assertSame(3, FinishType::Xtreme->points());
        $this->assertSame(2, FinishType::Over->points());
        $this->assertSame(2, FinishType::Burst->points());
        $this->assertSame(1, FinishType::Spin->points());
    }

    public function test_battle_resolves_when_points_to_win_reached(): void
    {
        $battle = $this->makeBattle(pointsToWin: 4);
        $bbA = Beyblade::create(['player_id' => $battle->player_a_id, 'name' => 'DranSword', 'generation' => 'beyblade_x']);
        $bbB = Beyblade::create(['player_id' => $battle->player_b_id, 'name' => 'HellsScythe', 'generation' => 'beyblade_x']);

        $svc = new ScoringService();

        // A: Xtreme(3) -> 3:0 ，尚未結束
        $battle = $svc->recordRound($battle, 'a', FinishType::Xtreme, $bbA->id, $bbB->id);
        $this->assertSame(BattleStatus::InProgress, $battle->status);
        $this->assertSame(3, $battle->score_a);

        // B: Spin(1) -> 3:1
        $battle = $svc->recordRound($battle, 'b', FinishType::Spin, $bbB->id, $bbA->id);
        $this->assertSame(1, $battle->score_b);

        // A: Spin(1) -> 4:1 ，達標結束，勝方為 A
        $battle = $svc->recordRound($battle, 'a', FinishType::Spin, $bbA->id, $bbB->id);
        $this->assertSame(BattleStatus::Finished, $battle->status);
        $this->assertSame($battle->player_a_id, $battle->winner_id);
        $this->assertNotNull($battle->finished_at);
    }

    public function test_round_robin_generates_full_schedule(): void
    {
        $svc = new BracketService();
        // 4 名選手 -> 3 輪，每輪 2 場 = 6 場（C(4,2)）
        $rounds = $svc->roundRobinSchedule([1, 2, 3, 4]);
        $this->assertCount(3, $rounds);
        $total = array_sum(array_map('count', $rounds));
        $this->assertSame(6, $total);
    }

    public function test_round_robin_handles_odd_players_with_byes(): void
    {
        $svc = new BracketService();
        // 5 名選手 -> 5 輪，每人一輪輪空，共 C(5,2)=10 場
        $rounds = $svc->roundRobinSchedule([1, 2, 3, 4, 5]);
        $this->assertCount(5, $rounds);
        $total = array_sum(array_map('count', $rounds));
        $this->assertSame(10, $total);
    }

    public function test_single_elimination_seeds_top_against_bye(): void
    {
        $svc = new BracketService();
        // 6 名 -> 補到 8，第一輪 4 場；種子1 對到輪空(null)
        $pairs = $svc->singleEliminationFirstRound([10, 20, 30, 40, 50, 60]);
        $this->assertCount(4, $pairs);

        // 種子1 (id=10) 應在第一場且對手為 null（輪空）
        $this->assertSame(10, $pairs[0][0]);
        $this->assertNull($pairs[0][1]);
    }
}
