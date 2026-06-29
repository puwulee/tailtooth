<?php

namespace Tests\Feature;

use App\Models\Battle;
use App\Models\Beyblade;
use App\Models\Division;
use App\Models\Player;
use App\Models\Stage;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefereeApiTest extends TestCase
{
    use RefreshDatabase;

    private function scenario(): array
    {
        $t = Tournament::create(['name' => 'Cup', 'event_date' => '2026-08-01', 'generation' => 'beyblade_x', 'rule_version' => '1']);
        $d = Division::create(['tournament_id' => $t->id, 'name' => 'D', 'age_group' => 'open', 'points_to_win' => 4]);
        $s = Stage::create(['division_id' => $d->id, 'type' => 'group', 'format' => 'round_robin']);
        $pa = Player::create(['real_name' => 'A']);
        $pb = Player::create(['real_name' => 'B']);
        $ba = Beyblade::create(['player_id' => $pa->id, 'name' => 'X', 'generation' => 'beyblade_x']);
        $bb = Beyblade::create(['player_id' => $pb->id, 'name' => 'Y', 'generation' => 'beyblade_x']);
        $battle = Battle::create(['stage_id' => $s->id, 'player_a_id' => $pa->id, 'player_b_id' => $pb->id]);

        return compact('battle', 'ba', 'bb');
    }

    public function test_record_round_via_api(): void
    {
        ['battle' => $battle, 'ba' => $ba, 'bb' => $bb] = $this->scenario();

        $res = $this->postJson("/api/battles/{$battle->id}/rounds", [
            'winner_side' => 'a', 'finish' => 'xtreme',
            'winner_beyblade_id' => $ba->id, 'loser_beyblade_id' => $bb->id,
        ]);

        $res->assertOk()->assertJsonPath('score_a', 3)->assertJsonPath('status', 'in_progress');
    }

    public function test_duplicate_client_event_id_is_idempotent(): void
    {
        ['battle' => $battle, 'ba' => $ba, 'bb' => $bb] = $this->scenario();
        $payload = [
            'winner_side' => 'a', 'finish' => 'over',
            'winner_beyblade_id' => $ba->id, 'loser_beyblade_id' => $bb->id,
            'client_event_id' => 'evt-123',
        ];

        $this->postJson("/api/battles/{$battle->id}/rounds", $payload)->assertOk();
        $this->postJson("/api/battles/{$battle->id}/rounds", $payload)->assertOk()->assertJsonPath('score_a', 2);

        $this->assertSame(1, $battle->fresh()->rounds()->count());
    }

    public function test_xtreme_rejected_for_non_x_generation(): void
    {
        ['battle' => $battle, 'ba' => $ba, 'bb' => $bb] = $this->scenario();
        $battle->stage->division->tournament->update(['generation' => 'burst']);

        $this->postJson("/api/battles/{$battle->id}/rounds", [
            'winner_side' => 'a', 'finish' => 'xtreme',
            'winner_beyblade_id' => $ba->id, 'loser_beyblade_id' => $bb->id,
        ])->assertStatus(422);
    }
}
