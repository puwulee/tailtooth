<?php

namespace Tests\Feature;

use App\Enums\BattleStatus;
use App\Enums\FinishType;
use App\Models\Battle;
use App\Models\Beyblade;
use App\Models\Division;
use App\Models\Player;
use App\Models\RankingPoint;
use App\Models\Registration;
use App\Models\Stage;
use App\Models\Tournament;
use App\Models\User;
use App\Services\Contracts\LineProfileFetcher;
use App\Services\LeaderboardService;
use App\Services\ScoringService;
use App\Services\StandingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoopClosingTest extends TestCase
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
        $t = Tournament::create(['name' => 'Cup', 'event_date' => now()->addDays(20)->toDateString(), 'generation' => 'beyblade_x', 'rule_version' => '1']);

        return Division::create(['tournament_id' => $t->id, 'name' => 'D', 'age_group' => 'open', 'points_to_win' => 4, 'fee' => 100]);
    }

    // ---- LINE Login ----

    public function test_line_login_callback_creates_and_logs_in_user(): void
    {
        $this->mock(LineProfileFetcher::class, function ($m) {
            $m->shouldReceive('fetch')->andReturn([
                'userId' => 'U123abc', 'displayName' => '阿藍', 'pictureUrl' => 'http://x/p.jpg', 'email' => null,
            ]);
        });

        session(['line_oauth_state' => 'st']);
        $res = $this->get('/auth/line/callback?code=xyz&state=st');
        $res->assertRedirect('/me');

        $this->assertAuthenticated();
        $user = User::where('line_user_id', 'U123abc')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasAnyRole('participant'));
    }

    public function test_line_callback_rejects_bad_state(): void
    {
        session(['line_oauth_state' => 'correct']);
        $this->get('/auth/line/callback?code=xyz&state=wrong')->assertStatus(419);
    }

    // ---- 名次 / 賽季積分結算 ----

    public function test_finalize_writes_ranking_points_and_feeds_leaderboard(): void
    {
        $division = $this->division();
        $stage = Stage::create(['division_id' => $division->id, 'type' => 'group', 'format' => 'round_robin']);
        $p1 = Player::create(['real_name' => 'A', 'nickname' => 'A']);
        $p2 = Player::create(['real_name' => 'B', 'nickname' => 'B']);

        // A 勝 B 4:0
        Battle::create(['stage_id' => $stage->id, 'player_a_id' => $p1->id, 'player_b_id' => $p2->id,
            'score_a' => 4, 'score_b' => 0, 'winner_id' => $p1->id, 'status' => BattleStatus::Finished]);

        $standings = (new StandingsService())->finalizeStage($stage);
        $this->assertSame($p1->id, $standings[0]['player_id']); // 第一名為 A

        $this->assertSame(100, RankingPoint::where('player_id', $p1->id)->value('points'));

        // 排行榜現在有資料
        $board = (new LeaderboardService())->seasonRanking();
        $this->assertNotEmpty($board);
        $this->assertSame($p1->id, $board[0]['player_id']);
    }

    // ---- 淘汰賽自動晉級 ----

    public function test_winner_auto_advances_to_next_battle(): void
    {
        $division = $this->division();
        $stage = Stage::create(['division_id' => $division->id, 'type' => 'playoff', 'format' => 'single_elim']);
        $p1 = Player::create(['real_name' => 'A']);
        $p2 = Player::create(['real_name' => 'B']);
        $bb1 = Beyblade::create(['player_id' => $p1->id, 'name' => 'x', 'generation' => 'beyblade_x']);
        $bb2 = Beyblade::create(['player_id' => $p2->id, 'name' => 'y', 'generation' => 'beyblade_x']);

        $final = Battle::create(['stage_id' => $stage->id, 'status' => BattleStatus::Pending]);
        $semi = Battle::create(['stage_id' => $stage->id, 'player_a_id' => $p1->id, 'player_b_id' => $p2->id,
            'next_battle_id' => $final->id, 'next_slot' => 0]);

        $svc = new ScoringService();
        for ($i = 0; $i < 4; $i++) {
            $svc->recordRound($semi, 'a', FinishType::Spin, $bb1->id, $bb2->id);
        }

        $this->assertSame($p1->id, $final->fresh()->player_a_id); // 勝者晉級到決賽 A 位
    }

    // ---- QR 報到 ----

    public function test_checkin_by_token(): void
    {
        $division = $this->division();
        $player = Player::create(['real_name' => 'A']);
        $reg = Registration::create(['division_id' => $division->id, 'player_id' => $player->id,
            'status' => 'paid', 'check_in_token' => 'TOKEN123']);

        $this->actingAs($this->staff('organizer'))
            ->postJson('/api/checkin/TOKEN123')
            ->assertOk()->assertJsonPath('ok', true);

        $this->assertSame('checked_in', $reg->fresh()->status->value);

        // 無效 token
        $this->actingAs($this->staff('organizer'))
            ->postJson('/api/checkin/NOPE')->assertStatus(404);
    }

    // ---- 後台報名管理 ----

    public function test_registration_admin_list_and_checkin(): void
    {
        $division = $this->division();
        $player = Player::create(['real_name' => '小明', 'nickname' => '阿明']);
        $reg = Registration::create(['division_id' => $division->id, 'player_id' => $player->id, 'status' => 'paid']);
        $tid = $division->tournament_id;

        $this->actingAs($this->staff('organizer'))
            ->getJson("/api/admin/registrations/{$tid}")
            ->assertOk()->assertJsonPath('0.player', '阿明');

        $this->actingAs($this->staff('organizer'))
            ->postJson("/api/admin/registrations/{$reg->id}/checkin")
            ->assertOk()->assertJsonPath('status', 'checked_in');
    }
}
