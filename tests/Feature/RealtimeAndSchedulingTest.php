<?php

namespace Tests\Feature;

use App\Enums\FinishType;
use App\Enums\RegistrationStatus;
use App\Events\BattleUpdated;
use App\Models\Battle;
use App\Models\Beyblade;
use App\Models\Division;
use App\Models\Player;
use App\Models\Registration;
use App\Models\Stage;
use App\Models\Tournament;
use App\Models\Venue;
use App\Services\ScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RealtimeAndSchedulingTest extends TestCase
{
    use RefreshDatabase;

    private function setupDivision(int $players = 4): array
    {
        $t = Tournament::create(['name' => 'Cup', 'event_date' => '2026-08-01', 'generation' => 'beyblade_x', 'rule_version' => '1']);
        $d = Division::create(['tournament_id' => $t->id, 'name' => '成人組', 'age_group' => 'adult', 'points_to_win' => 4]);

        $ids = [];
        for ($i = 1; $i <= $players; $i++) {
            $p = Player::create(['real_name' => "P$i"]);
            Registration::create(['division_id' => $d->id, 'player_id' => $p->id, 'status' => RegistrationStatus::Paid->value]);
            $ids[] = $p->id;
        }

        return ['tournament' => $t, 'division' => $d, 'players' => $ids];
    }

    public function test_battle_updated_event_broadcasts_on_tournament_channel(): void
    {
        $ctx = $this->setupDivision(2);
        $stage = Stage::create(['division_id' => $ctx['division']->id, 'type' => 'group', 'format' => 'round_robin']);
        $battle = Battle::create(['stage_id' => $stage->id, 'player_a_id' => $ctx['players'][0], 'player_b_id' => $ctx['players'][1]]);

        $event = new BattleUpdated($battle);
        $this->assertSame("tournament.{$ctx['tournament']->id}", $event->broadcastOn()->name);
        $this->assertSame('battle.updated', $event->broadcastAs());
    }

    public function test_scoring_dispatches_realtime_event(): void
    {
        Event::fake([BattleUpdated::class]);

        $ctx = $this->setupDivision(2);
        $stage = Stage::create(['division_id' => $ctx['division']->id, 'type' => 'group', 'format' => 'round_robin']);
        $battle = Battle::create(['stage_id' => $stage->id, 'player_a_id' => $ctx['players'][0], 'player_b_id' => $ctx['players'][1]]);
        $bb = Beyblade::create(['player_id' => $ctx['players'][0], 'name' => 'X', 'generation' => 'beyblade_x']);

        (new ScoringService())->recordRound($battle, 'a', FinishType::Spin, $bb->id, $bb->id);

        Event::assertDispatched(BattleUpdated::class);
    }

    public function test_generate_round_robin_schedule(): void
    {
        $ctx = $this->setupDivision(4);

        $res = $this->postJson("/api/scheduling/divisions/{$ctx['division']->id}/generate", [
            'type' => 'group', 'format' => 'round_robin',
        ]);

        $res->assertCreated()->assertJsonPath('battles', 6); // C(4,2)=6
    }

    public function test_generate_single_elim_with_bye(): void
    {
        $ctx = $this->setupDivision(3); // 補到 4，第一輪 2 場，含一個輪空

        $res = $this->postJson("/api/scheduling/divisions/{$ctx['division']->id}/generate", [
            'type' => 'playoff', 'format' => 'single_elim',
        ]);

        $res->assertCreated()->assertJsonPath('battles', 2);
    }

    public function test_generate_rejects_insufficient_players(): void
    {
        $ctx = $this->setupDivision(1);

        $this->postJson("/api/scheduling/divisions/{$ctx['division']->id}/generate", [
            'type' => 'group', 'format' => 'round_robin',
        ])->assertStatus(422);
    }

    public function test_overview_and_battles_endpoints(): void
    {
        $ctx = $this->setupDivision(4);
        $this->postJson("/api/scheduling/divisions/{$ctx['division']->id}/generate", ['type' => 'group', 'format' => 'round_robin'])->assertCreated();

        $this->getJson("/api/scheduling/{$ctx['tournament']->id}/overview")
            ->assertOk()->assertJsonPath('divisions.0.ready_players', 4);

        $this->getJson("/api/scheduling/divisions/{$ctx['division']->id}/battles")
            ->assertOk()->assertJsonCount(1); // 一個賽段
    }

    public function test_draw_venue_is_recorded(): void
    {
        $ctx = $this->setupDivision(2);
        $stage = Stage::create(['division_id' => $ctx['division']->id, 'type' => 'final', 'format' => 'single_elim']);
        $battle = Battle::create(['stage_id' => $stage->id, 'player_a_id' => $ctx['players'][0], 'player_b_id' => $ctx['players'][1]]);
        $v1 = Venue::create(['name' => '場地一']);
        $v2 = Venue::create(['name' => '場地二']);

        $this->postJson("/api/scheduling/battles/{$battle->id}/draw-venue", ['venue_ids' => [$v1->id, $v2->id]])
            ->assertOk();

        $this->assertTrue($battle->fresh()->venue_drawn);
        $this->assertNotNull($battle->fresh()->venue_id);
    }
}
