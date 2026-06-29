<?php

namespace Tests\Feature;

use App\Models\Battle;
use App\Models\Beyblade;
use App\Models\Division;
use App\Models\EquipmentCheck;
use App\Models\Player;
use App\Models\Registration;
use App\Models\Stage;
use App\Models\Tournament;
use App\Models\User;
use App\Models\Venue;
use App\Services\ArchiveService;
use App\Services\BracketService;
use App\Services\PdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegulationTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u->load('roles');
    }

    private function division(): Division
    {
        $t = Tournament::create(['name' => '夏季盃', 'event_date' => '2026-08-01', 'generation' => 'beyblade_x', 'rule_version' => '1']);

        return Division::create(['tournament_id' => $t->id, 'name' => 'D', 'age_group' => 'open', 'points_to_win' => 4]);
    }

    // ---- 裝備驗規 ----

    public function test_equipment_check_records_pass_fail(): void
    {
        $division = $this->division();
        $player = Player::create(['real_name' => 'A']);
        $reg = Registration::create(['division_id' => $division->id, 'player_id' => $player->id, 'status' => 'checked_in']);
        $bey = Beyblade::create(['player_id' => $player->id, 'name' => 'x', 'generation' => 'beyblade_x']);

        $referee = $this->staff('referee');

        $this->actingAs($referee)->getJson("/api/equipment/{$division->id}/data")
            ->assertOk()->assertJsonPath('0.player', 'A');

        $this->actingAs($referee)->postJson('/api/equipment/record', [
            'registration_id' => $reg->id, 'beyblade_id' => $bey->id, 'passed' => false, 'note' => '非官方零件',
        ])->assertOk()->assertJsonPath('passed', false);

        $this->assertSame(1, EquipmentCheck::where('registration_id', $reg->id)->count());

        // 再次登錄為通過（updateOrCreate 不重複）
        $this->actingAs($referee)->postJson('/api/equipment/record', [
            'registration_id' => $reg->id, 'beyblade_id' => $bey->id, 'passed' => true,
        ])->assertOk();
        $this->assertSame(1, EquipmentCheck::where('registration_id', $reg->id)->count());
        $this->assertTrue((bool) EquipmentCheck::first()->passed);
    }

    // ---- 賽程時段衝突 ----

    public function test_schedule_conflict_detects_venue_and_player_clash(): void
    {
        $division = $this->division();
        $stage = Stage::create(['division_id' => $division->id, 'type' => 'group', 'format' => 'round_robin']);
        $venue = Venue::create(['name' => 'A 場']);
        $p1 = Player::create(['real_name' => 'A']);
        $p2 = Player::create(['real_name' => 'B']);
        $p3 = Player::create(['real_name' => 'C']);

        // 同場地、同時段 → 撞場；且 p1 同時段兩場 → 選手撞場
        Battle::create(['stage_id' => $stage->id, 'venue_id' => $venue->id, 'player_a_id' => $p1->id, 'player_b_id' => $p2->id, 'scheduled_at' => '2026-08-01 10:00:00']);
        Battle::create(['stage_id' => $stage->id, 'venue_id' => $venue->id, 'player_a_id' => $p1->id, 'player_b_id' => $p3->id, 'scheduled_at' => '2026-08-01 10:05:00']);

        $conflicts = (new BracketService())->detectScheduleConflicts($stage);
        $this->assertNotEmpty($conflicts);
        $this->assertTrue(collect($conflicts)->contains(fn ($c) => str_contains($c, '場地撞場')));
        $this->assertTrue(collect($conflicts)->contains(fn ($c) => str_contains($c, '選手撞場')));
    }

    public function test_no_conflict_when_times_apart(): void
    {
        $division = $this->division();
        $stage = Stage::create(['division_id' => $division->id, 'type' => 'group', 'format' => 'round_robin']);
        $venue = Venue::create(['name' => 'A 場']);
        $p1 = Player::create(['real_name' => 'A']);
        $p2 = Player::create(['real_name' => 'B']);

        Battle::create(['stage_id' => $stage->id, 'venue_id' => $venue->id, 'player_a_id' => $p1->id, 'player_b_id' => $p2->id, 'scheduled_at' => '2026-08-01 10:00:00']);
        Battle::create(['stage_id' => $stage->id, 'venue_id' => $venue->id, 'player_a_id' => $p1->id, 'player_b_id' => $p2->id, 'scheduled_at' => '2026-08-01 11:00:00']);

        $this->assertEmpty((new BracketService())->detectScheduleConflicts($stage));
    }

    // ---- PDF 匯出 ----

    public function test_pdf_export_produces_pdf_bytes(): void
    {
        $division = $this->division();
        $tournament = $division->tournament;
        $stage = Stage::create(['division_id' => $division->id, 'type' => 'group', 'format' => 'round_robin']);
        $p1 = Player::create(['real_name' => '小藍', 'nickname' => '藍焰']);
        $p2 = Player::create(['real_name' => '小紅', 'nickname' => '紅蓮']);
        Battle::create(['stage_id' => $stage->id, 'player_a_id' => $p1->id, 'player_b_id' => $p2->id,
            'score_a' => 4, 'score_b' => 1, 'winner_id' => $p1->id, 'status' => 'finished']);

        $results = (new ArchiveService())->tournamentResults($tournament);
        $pdf = (new PdfService())->render((new ArchiveService())->toHtmlBody($results), '成績');

        $this->assertStringStartsWith('%PDF', $pdf);

        // 端點
        $this->get("/api/broadcast/{$tournament->id}/archive?format=pdf")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }
}
