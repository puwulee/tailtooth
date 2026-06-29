<?php

namespace Tests\Feature;

use App\Enums\Authenticity;
use App\Enums\AuthenticityPolicy;
use App\Enums\FinishType;
use App\Models\Battle;
use App\Models\Beyblade;
use App\Models\Division;
use App\Models\Player;
use App\Models\ScoreRule;
use App\Models\Stage;
use App\Models\Tournament;
use App\Services\BeybladePhotoService;
use App\Services\BeybladeStatsService;
use App\Services\BackgroundRemovers\NullBackgroundRemover;
use App\Services\ScoringService;
use App\Services\TournamentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticity_policy_filters_beyblade_kind(): void
    {
        $this->assertTrue(AuthenticityPolicy::OfficialOnly->allows(Authenticity::Official));
        $this->assertFalse(AuthenticityPolicy::OfficialOnly->allows(Authenticity::Replica));
        $this->assertTrue(AuthenticityPolicy::Both->allows(Authenticity::Replica));
    }

    public function test_combo_signature_is_order_independent(): void
    {
        $a = Beyblade::create([
            'player_id' => Player::create(['real_name' => 'A'])->id,
            'name' => 'combo1', 'generation' => 'beyblade_x',
            'parts' => ['blade' => 'DranSword', 'ratchet' => '3-60', 'bit' => 'Flat'],
        ]);
        $b = Beyblade::create([
            'player_id' => Player::create(['real_name' => 'B'])->id,
            'name' => 'combo2', 'generation' => 'beyblade_x',
            'parts' => ['bit' => 'Flat', 'ratchet' => '3-60', 'blade' => 'DranSword'],
        ]);

        $this->assertNotNull($a->combo_signature);
        $this->assertSame($a->combo_signature, $b->combo_signature);
    }

    public function test_clone_tournament_copies_divisions_and_rules(): void
    {
        $src = Tournament::create([
            'name' => '原賽事', 'event_date' => '2026-08-01',
            'generation' => 'beyblade_x', 'rule_version' => '2026.1',
            'authenticity_policy' => 'official_only', 'is_template' => true,
        ]);
        $div = Division::create([
            'tournament_id' => $src->id, 'name' => '成人組', 'age_group' => 'adult',
        ]);
        ScoreRule::create(['division_id' => $div->id, 'finish_type' => 'xtreme', 'points' => 3]);

        $clone = app(TournamentService::class)->cloneTournament($src, [
            'name' => '推廣場', 'event_date' => '2026-09-01',
        ]);

        $this->assertSame($src->id, $clone->cloned_from_id);
        $this->assertSame('推廣場', $clone->name);
        $this->assertFalse($clone->is_template);
        $this->assertCount(1, $clone->divisions);
        $this->assertSame(3, ScoreRule::where('division_id', $clone->divisions->first()->id)->value('points'));
    }

    public function test_beyblade_stats_track_win_rate_and_points(): void
    {
        $tournament = Tournament::create([
            'name' => 'Cup', 'event_date' => '2026-08-01',
            'generation' => 'beyblade_x', 'rule_version' => '1',
        ]);
        $division = Division::create([
            'tournament_id' => $tournament->id, 'name' => 'D', 'age_group' => 'open', 'points_to_win' => 4,
        ]);
        $stage = Stage::create(['division_id' => $division->id, 'type' => 'group', 'format' => 'round_robin']);

        $pa = Player::create(['real_name' => 'A']);
        $pb = Player::create(['real_name' => 'B']);
        $bbA = Beyblade::create(['player_id' => $pa->id, 'name' => 'X', 'generation' => 'beyblade_x', 'parts' => ['blade' => 'Dran']]);
        $bbB = Beyblade::create(['player_id' => $pb->id, 'name' => 'Y', 'generation' => 'beyblade_x', 'parts' => ['blade' => 'Hells']]);

        $battle = Battle::create(['stage_id' => $stage->id, 'player_a_id' => $pa->id, 'player_b_id' => $pb->id]);
        $svc = new ScoringService();
        $svc->recordRound($battle, 'a', FinishType::Xtreme, $bbA->id, $bbB->id); // A win 3
        $svc->recordRound($battle, 'b', FinishType::Spin, $bbB->id, $bbA->id);   // B win 1
        $svc->recordRound($battle, 'a', FinishType::Spin, $bbA->id, $bbB->id);   // A win 1 -> 4

        $stats = (new BeybladeStatsService())->forBeyblade($bbA->id);
        $this->assertSame(3, $stats['appearances']);
        $this->assertSame(2, $stats['wins']);
        $this->assertSame(1, $stats['losses']);
        $this->assertEqualsWithDelta(0.6667, $stats['win_rate'], 0.001);
        $this->assertSame(4, $stats['points_scored']);

        $combos = (new BeybladeStatsService())->comboLeaderboard();
        $this->assertNotEmpty($combos);
        // 最佳 combo 應為 A 的（勝率較高）
        $this->assertSame($bbA->combo_signature, $combos[0]['combo_signature']);
    }

    public function test_photo_normalize_outputs_uniform_dimensions(): void
    {
        config(['beyblade.photo.canvas_width' => 400, 'beyblade.photo.canvas_height' => 400]);

        // 造一張 120x300 的測試圖
        $img = imagecreatetruecolor(120, 300);
        imagefill($img, 0, 0, imagecolorallocate($img, 200, 50, 50));
        ob_start();
        imagepng($img);
        $bytes = ob_get_clean();
        imagedestroy($img);

        $service = new BeybladePhotoService(new NullBackgroundRemover());
        $out = $service->normalizeAndWatermark($bytes);

        $result = imagecreatefromstring($out);
        $this->assertSame(400, imagesx($result));
        $this->assertSame(400, imagesy($result));
    }
}
