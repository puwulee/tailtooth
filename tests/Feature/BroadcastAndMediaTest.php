<?php

namespace Tests\Feature;

use App\Enums\BattleStatus;
use App\Models\Battle;
use App\Models\Division;
use App\Models\Player;
use App\Models\Stage;
use App\Models\Tournament;
use App\Services\ArchiveService;
use App\Services\BoardService;
use App\Services\PlayerPhotoService;
use App\Services\SocialCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BroadcastAndMediaTest extends TestCase
{
    use RefreshDatabase;

    private function finishedBattle(): Battle
    {
        $t = Tournament::create(['name' => 'Cup', 'event_date' => '2026-08-01', 'generation' => 'beyblade_x', 'rule_version' => '1', 'stream_url' => 'https://example.com/live']);
        $d = Division::create(['tournament_id' => $t->id, 'name' => '成人組', 'age_group' => 'adult']);
        $s = Stage::create(['division_id' => $d->id, 'type' => 'group', 'format' => 'round_robin']);
        $pa = Player::create(['real_name' => 'Aoi', 'nickname' => 'AOI']);
        $pb = Player::create(['real_name' => 'Ren', 'nickname' => 'REN']);

        return Battle::create([
            'stage_id' => $s->id, 'player_a_id' => $pa->id, 'player_b_id' => $pb->id,
            'score_a' => 4, 'score_b' => 2, 'winner_id' => $pa->id, 'status' => BattleStatus::Finished,
        ]);
    }

    public function test_board_data_groups_live_upcoming_recent(): void
    {
        $battle = $this->finishedBattle();
        $tournament = $battle->stage->division->tournament;

        $data = (new BoardService())->forTournament($tournament);

        $this->assertSame('https://example.com/live', $data['tournament']['stream_url']);
        $this->assertCount(1, $data['recent']);
        $this->assertSame('4', (string) $data['recent'][0]['a']['score']);
    }

    public function test_broadcast_data_endpoint_returns_json(): void
    {
        $battle = $this->finishedBattle();
        $tid = $battle->stage->division->tournament_id;

        $this->getJson("/api/broadcast/{$tid}/data")
            ->assertOk()
            ->assertJsonStructure(['tournament' => ['name', 'stream_url'], 'live', 'upcoming', 'recent']);
    }

    public function test_broadcast_tv_page_renders(): void
    {
        $battle = $this->finishedBattle();
        $tid = $battle->stage->division->tournament_id;

        $this->get("/broadcast/{$tid}")->assertOk()->assertSee('直播', false);
    }

    public function test_social_card_produces_valid_png(): void
    {
        $battle = $this->finishedBattle();
        $bytes = (new SocialCardService())->resultCard($battle);

        $img = imagecreatefromstring($bytes);
        $this->assertNotFalse($img);
        $this->assertSame(1200, imagesx($img));
    }

    public function test_avatar_square_crop(): void
    {
        $src = imagecreatetruecolor(300, 120);
        imagefill($src, 0, 0, imagecolorallocate($src, 10, 200, 80));
        ob_start();
        imagepng($src);
        $bytes = ob_get_clean();

        $out = (new PlayerPhotoService())->squareCrop($bytes, 256);
        $img = imagecreatefromstring($out);
        $this->assertSame(256, imagesx($img));
        $this->assertSame(256, imagesy($img));
    }

    public function test_archive_results_html(): void
    {
        $battle = $this->finishedBattle();
        $tournament = $battle->stage->division->tournament;

        $svc = new ArchiveService();
        $results = $svc->tournamentResults($tournament);
        $this->assertSame(1, $results['total_battles']);

        $html = $svc->toHtml($results);
        $this->assertStringContainsString('成績公告', $html);
    }
}
