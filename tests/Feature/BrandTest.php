<?php

namespace Tests\Feature;

use App\Enums\BattleStatus;
use App\Enums\FinishType;
use App\Models\Battle;
use App\Models\Beyblade;
use App\Models\Division;
use App\Models\Player;
use App\Models\RankingPoint;
use App\Models\Stage;
use App\Models\Tournament;
use App\Models\User;
use App\Services\PlayerProfileService;
use App\Services\ScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u->load('roles');
    }

    public function test_rankings_data_endpoint_is_public(): void
    {
        $this->getJson('/api/rankings/data')
            ->assertOk()->assertJsonStructure(['players', 'combos']);
    }

    public function test_player_profile_awards_champion_badge(): void
    {
        $t = Tournament::create(['name' => 'Cup', 'event_date' => '2026-08-01', 'generation' => 'beyblade_x', 'rule_version' => '1']);
        $d = Division::create(['tournament_id' => $t->id, 'name' => 'D', 'age_group' => 'open', 'points_to_win' => 4]);
        $stage = Stage::create(['division_id' => $d->id, 'type' => 'group', 'format' => 'round_robin']);
        $p1 = Player::create(['real_name' => 'A', 'nickname' => '冠軍王']);
        $p2 = Player::create(['real_name' => 'B']);
        $b1 = Beyblade::create(['player_id' => $p1->id, 'name' => 'x', 'generation' => 'beyblade_x']);
        $b2 = Beyblade::create(['player_id' => $p2->id, 'name' => 'y', 'generation' => 'beyblade_x']);

        $battle = Battle::create(['stage_id' => $stage->id, 'player_a_id' => $p1->id, 'player_b_id' => $p2->id]);
        $svc = new ScoringService();
        $svc->recordRound($battle, 'a', FinishType::Xtreme, $b1->id, $b2->id);
        $svc->recordRound($battle, 'a', FinishType::Spin, $b1->id, $b2->id);

        RankingPoint::create(['player_id' => $p1->id, 'tournament_id' => $t->id, 'division_id' => $d->id, 'final_rank' => 1, 'points' => 100]);

        $profile = (new PlayerProfileService())->profile($p1->fresh());
        $this->assertSame(100, $profile['season_points']);
        $this->assertSame(1, $profile['best_rank']);
        $this->assertTrue(collect($profile['badges'])->contains('label', '冠軍'));

        // 公開選手檔案頁可瀏覽
        $this->get("/players/{$p1->id}")->assertOk()->assertSee('冠軍王', false);
    }

    public function test_sponsor_crud_requires_platform_and_works(): void
    {
        Storage::fake('public');

        // 裁判不可管理贊助
        $this->actingAs($this->staff('referee'))
            ->postJson('/api/admin/sponsors', ['name' => 'X'])->assertStatus(403);

        // 平台可新增（含 Logo）
        $this->actingAs($this->staff('platform'))->post('/api/admin/sponsors', [
            'name' => '陀螺工坊', 'tier' => '黃金',
            'logo' => UploadedFile::fake()->image('logo.png', 200, 80),
        ])->assertCreated();

        $this->assertDatabaseHas('sponsors', ['name' => '陀螺工坊', 'tier' => '黃金']);

        // 公開首頁顯示贊助商
        $this->get('/')->assertOk()->assertSee('陀螺工坊', false);
    }

    public function test_locale_switch_translates_home(): void
    {
        // 英文
        $this->get('/?lang=en')->assertOk()->assertSee('Beyblade Competition Platform', false);
        // 切回中文（記憶於 session）
        $this->get('/?lang=zh_TW')->assertOk()->assertSee('戰鬥陀螺競賽平台', false);
    }
}
