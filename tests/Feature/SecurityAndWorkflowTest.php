<?php

namespace Tests\Feature;

use App\Enums\BattleStatus;
use App\Models\Appeal;
use App\Models\Battle;
use App\Models\Division;
use App\Models\Player;
use App\Models\Stage;
use App\Models\Tournament;
use App\Models\User;
use App\Services\AppealService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityAndWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u->load('roles');
    }

    private function tournament(): Tournament
    {
        return Tournament::create(['name' => 'Cup', 'event_date' => '2026-08-01', 'generation' => 'beyblade_x', 'rule_version' => '1']);
    }

    // ---- RBAC ----

    public function test_scheduling_requires_organizer_role(): void
    {
        $t = $this->tournament();

        // 未登入 → 重導登入（或 401/403）
        $this->getJson("/api/scheduling/{$t->id}/overview")->assertStatus(401);

        // 裁判角色 → 403（權限不足）
        $this->actingAs($this->user('referee'))
            ->getJson("/api/scheduling/{$t->id}/overview")->assertStatus(403);

        // 主辦角色 → 通過
        $this->actingAs($this->user('organizer'))
            ->getJson("/api/scheduling/{$t->id}/overview")->assertOk();
    }

    public function test_settings_requires_platform_role(): void
    {
        $this->actingAs($this->user('organizer'))
            ->getJson('/api/admin/settings')->assertStatus(403);

        $this->actingAs($this->user('platform'))
            ->getJson('/api/admin/settings')->assertOk();
    }

    // ---- 後台設定（API 金鑰）----

    public function test_secret_setting_is_encrypted_and_masked(): void
    {
        $svc = app(SettingsService::class);
        $svc->set('linepay.channel_secret', 'super-secret-1234', 'payment', true);

        // DB 內不應是明文
        $raw = \DB::table('settings')->where('key', 'linepay.channel_secret')->value('value');
        $this->assertNotSame('super-secret-1234', $raw);

        // 讀取解密正確、遮罩只露末四碼
        $this->assertSame('super-secret-1234', $svc->get('linepay.channel_secret'));
        $this->assertSame('••••1234', $svc->masked('linepay.channel_secret'));
    }

    public function test_settings_update_endpoint_skips_blanks(): void
    {
        $svc = app(SettingsService::class);
        $svc->set('line.channel_access_token', 'existing-token', 'line', true);

        $this->actingAs($this->user('platform'))->postJson('/api/admin/settings', [
            'settings' => ['line.channel_access_token' => ''], // 空白不覆寫
        ])->assertOk();

        $this->assertSame('existing-token', app(SettingsService::class)->get('line.channel_access_token'));
    }

    // ---- 申訴審理 ----

    private function finishedBattle(): Battle
    {
        $t = $this->tournament();
        $d = Division::create(['tournament_id' => $t->id, 'name' => 'D', 'age_group' => 'open', 'points_to_win' => 4]);
        $s = Stage::create(['division_id' => $d->id, 'type' => 'group', 'format' => 'round_robin']);
        $pa = Player::create(['real_name' => 'A']);
        $pb = Player::create(['real_name' => 'B']);

        return Battle::create([
            'stage_id' => $s->id, 'player_a_id' => $pa->id, 'player_b_id' => $pb->id,
            'score_a' => 4, 'score_b' => 1, 'winner_id' => $pa->id, 'status' => BattleStatus::Finished,
        ]);
    }

    public function test_appeal_uphold_resets_battle(): void
    {
        $battle = $this->finishedBattle();
        $player = $battle->playerA;
        $svc = app(AppealService::class);

        $appeal = $svc->file($battle, $player, '判定有誤');
        $this->assertSame(BattleStatus::Appealed, $battle->fresh()->status);

        $referee = $this->user('referee');
        $svc->rule($appeal, $referee->id, 'uphold', '重新比賽');

        $fresh = $battle->fresh();
        $this->assertSame(BattleStatus::InProgress, $fresh->status);
        $this->assertSame(0, $fresh->score_a);
        $this->assertNull($fresh->winner_id);
    }

    public function test_appeal_reject_confirms_battle(): void
    {
        $battle = $this->finishedBattle();
        $svc = app(AppealService::class);
        $appeal = $svc->file($battle, $battle->playerA, '不服');

        $svc->rule($appeal, $this->user('organizer')->id, 'reject');

        $this->assertSame(BattleStatus::Confirmed, $battle->fresh()->status);
        $this->assertSame('rejected', $appeal->fresh()->status);
    }

    // ---- 社群一鍵發布 ----

    public function test_publish_battle_generates_card_and_caption(): void
    {
        Storage::fake('public');
        $battle = $this->finishedBattle();

        $res = $this->actingAs($this->user('organizer'))
            ->postJson("/api/publish/battle/{$battle->id}");

        $res->assertOk()->assertJsonStructure(['image_url', 'caption']);
        Storage::disk('public')->assertExists("cards/battle-{$battle->id}.png");
    }
}
