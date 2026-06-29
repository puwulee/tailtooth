<?php

namespace Tests\Feature;

use App\Enums\BattleStatus;
use App\Models\Battle;
use App\Models\Division;
use App\Models\Player;
use App\Models\Stage;
use App\Models\Tournament;
use App\Services\SocialCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaExportTest extends TestCase
{
    use RefreshDatabase;

    private function finishedBattle(): Battle
    {
        $t = Tournament::create(['name' => '夏季盃', 'event_date' => '2026-08-01', 'generation' => 'beyblade_x', 'rule_version' => '1']);
        $d = Division::create(['tournament_id' => $t->id, 'name' => '成人組', 'age_group' => 'adult']);
        $s = Stage::create(['division_id' => $d->id, 'type' => 'group', 'format' => 'round_robin']);
        $pa = Player::create(['real_name' => '小藍', 'nickname' => '藍焰']);
        $pb = Player::create(['real_name' => '小紅', 'nickname' => '紅蓮']);

        return Battle::create([
            'stage_id' => $s->id, 'player_a_id' => $pa->id, 'player_b_id' => $pb->id,
            'score_a' => 4, 'score_b' => 1, 'winner_id' => $pa->id, 'status' => BattleStatus::Finished,
        ]);
    }

    public function test_social_card_renders_chinese_png(): void
    {
        $card = (new SocialCardService())->resultCard($this->finishedBattle());
        $img = imagecreatefromstring($card);

        $this->assertNotFalse($img);
        $this->assertSame(1200, imagesx($img));
        // 字型存在時應有非背景色像素（中文已繪出）
        $this->assertGreaterThan(2000, strlen($card));
    }

    public function test_battle_card_download_endpoint(): void
    {
        $battle = $this->finishedBattle();

        $res = $this->get("/api/battles/{$battle->id}/card");
        $res->assertOk();
        $this->assertSame('image/png', $res->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $res->headers->get('Content-Disposition'));
    }

    public function test_archive_export_html_and_json(): void
    {
        $battle = $this->finishedBattle();
        $tid = $battle->stage->division->tournament_id;

        $this->get("/api/broadcast/{$tid}/archive")
            ->assertOk()
            ->assertSee('成績公告', false);

        $this->getJson("/api/broadcast/{$tid}/archive?format=json")
            ->assertOk()
            ->assertJsonPath('total_battles', 1);
    }
}
