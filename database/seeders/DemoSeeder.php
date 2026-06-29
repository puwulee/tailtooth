<?php

namespace Database\Seeders;

use App\Enums\BattleStatus;
use App\Enums\FinishType;
use App\Enums\RegistrationStatus;
use App\Models\Battle;
use App\Models\Beyblade;
use App\Models\Division;
use App\Models\Player;
use App\Models\Registration;
use App\Models\Stage;
use App\Models\Tournament;
use App\Models\Venue;
use App\Services\AppealService;
use App\Services\ScoringService;
use Illuminate\Database\Seeder;

/** 示範資料：供畫面展示用。 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $tournament = Tournament::create([
            'name' => '2026 夏季戰鬥陀螺公開賽',
            'event_date' => '2026-07-12',
            'generation' => 'beyblade_x',
            'rule_version' => '2026.1',
            'status' => 'ongoing',
            'stream_url' => 'https://www.youtube.com/embed/jfKfPfyJRdk',
            'prizes' => ['冠軍' => '限定金屬陀螺 + 獎盃', '亞軍' => '官方發射器組'],
        ]);

        $venueA = Venue::create(['name' => '台北會展中心 A 場', 'address' => '台北市信義區', 'lat' => 25.0339, 'lng' => 121.5645, 'approval_status' => 'approved', 'is_sponsored' => true]);
        $venueB = Venue::create(['name' => '青少年活動中心 B 場', 'address' => '台北市大安區', 'lat' => 25.0265, 'lng' => 121.5436, 'approval_status' => 'approved']);

        $division = Division::create([
            'tournament_id' => $tournament->id, 'name' => '成人組', 'age_group' => 'adult',
            'deck_mode' => 'deck', 'deck_size' => 3, 'points_to_win' => 4, 'fee' => 500, 'capacity' => 16,
        ]);
        Division::create([
            'tournament_id' => $tournament->id, 'name' => '兒童組', 'age_group' => 'kids',
            'deck_mode' => 'deck', 'deck_size' => 3, 'points_to_win' => 4, 'fee' => 300, 'capacity' => 16,
        ]);

        $names = [['藍焰', '陳柏宇'], ['紅蓮', '林子翔'], ['雷霆', '黃尚恩'], ['暗影', '吳承翰'], ['疾風', '張立威'], ['炎龍', '李宥辰']];
        $players = [];
        foreach ($names as [$nick, $real]) {
            $p = Player::create(['real_name' => $real, 'nickname' => $nick, 'guardian_consent' => true]);
            Registration::create([
                'division_id' => $division->id, 'player_id' => $p->id,
                'status' => RegistrationStatus::CheckedIn->value, 'paid_at' => now(), 'checked_in_at' => now(),
                'invoice_number' => 'INV-' . str_pad((string) $p->id, 8, '0', STR_PAD_LEFT),
            ]);
            Beyblade::create(['player_id' => $p->id, 'name' => $nick . '號', 'generation' => 'beyblade_x', 'authenticity' => 'official',
                'parts' => ['blade' => 'DranSword', 'ratchet' => '3-60', 'bit' => 'Flat'], 'photo_status' => 'done']);
            $players[] = $p;
        }

        $stage = Stage::create(['division_id' => $division->id, 'type' => 'group', 'format' => 'round_robin', 'sequence' => 1]);
        $scoring = new ScoringService();

        // 已結束場次（含比分與回合）
        $finished = [[0, 1, [['a', FinishType::Xtreme], ['b', FinishType::Spin], ['a', FinishType::Over]]],
                     [2, 3, [['a', FinishType::Burst], ['a', FinishType::Over]]]];
        foreach ($finished as [$ai, $bi, $rounds]) {
            $b = Battle::create(['stage_id' => $stage->id, 'venue_id' => $venueA->id, 'player_a_id' => $players[$ai]->id, 'player_b_id' => $players[$bi]->id]);
            foreach ($rounds as [$side, $finish]) {
                $win = $side === 'a' ? $players[$ai] : $players[$bi];
                $lose = $side === 'a' ? $players[$bi] : $players[$ai];
                $scoring->recordRound($b, $side, $finish, $win->beyblades->first()->id, $lose->beyblades->first()->id);
            }
        }

        // 進行中場次
        $live = Battle::create(['stage_id' => $stage->id, 'venue_id' => $venueB->id, 'player_a_id' => $players[4]->id, 'player_b_id' => $players[5]->id]);
        $scoring->recordRound($live, 'a', FinishType::Over, $players[4]->beyblades->first()->id, $players[5]->beyblades->first()->id);
        $scoring->recordRound($live, 'b', FinishType::Burst, $players[5]->beyblades->first()->id, $players[4]->beyblades->first()->id);

        // 即將開始場次
        Battle::create(['stage_id' => $stage->id, 'venue_id' => $venueA->id, 'player_a_id' => $players[0]->id, 'player_b_id' => $players[2]->id]);
        Battle::create(['stage_id' => $stage->id, 'venue_id' => $venueB->id, 'player_a_id' => $players[1]->id, 'player_b_id' => $players[3]->id]);

        // 示範參賽者帳號（綁定第一位選手），可登入前台「我的後台」
        $participantUser = \App\Models\User::updateOrCreate(
            ['email' => 'player@tailtooth.local'],
            ['name' => $players[0]->real_name, 'password' => \Illuminate\Support\Facades\Hash::make('password')],
        );
        $participantUser->assignRole('participant');
        $players[0]->update(['user_id' => $participantUser->id, 'phone' => '0912-345-678']);

        // 產生示範用大頭照與一顆處理完成的陀螺圖（GD），讓「我的後台」有圖可看
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        $avatar = $this->solidPng(200, 200, [47, 123, 255], '藍');
        $disk->put('demo/avatar.png', $avatar);
        $players[0]->update(['avatar_path' => 'demo/avatar.png']);

        $beyImg = $this->solidPng(400, 400, [20, 26, 40], 'X');
        $disk->put('demo/bey.png', $beyImg);
        $players[0]->beyblades()->first()->update(['watermarked_path' => 'demo/bey.png', 'photo_status' => 'done']);
        $players[0]->beyblades()->create(['name' => '藍焰二號', 'generation' => 'beyblade_x', 'authenticity' => 'replica', 'photo_status' => 'processing']);

        // 結算賽季積分（讓排行榜/選手檔案有資料）
        app(\App\Services\StandingsService::class)->finalizeStage($stage);

        // 贊助商（露出於首頁/看板/直播）
        \App\Models\Sponsor::create(['name' => '極速陀螺工坊', 'tier' => '鑽石']);
        \App\Models\Sponsor::create(['name' => 'X 戰魂飲料', 'tier' => '黃金']);

        // 一筆待審申訴（取一場已結束的）
        $appealBattle = Battle::where('stage_id', $stage->id)->where('status', BattleStatus::Finished)->first();
        if ($appealBattle) {
            app(AppealService::class)->file($appealBattle, $appealBattle->playerA, '裁判判定出場時陀螺仍在旋轉，應為持續力勝而非出場勝。');
        }
    }

    /** 產生純色 + 文字的 PNG（示範圖）。 */
    private function solidPng(int $w, int $h, array $rgb, string $label): string
    {
        $img = imagecreatetruecolor($w, $h);
        imagefill($img, 0, 0, imagecolorallocate($img, ...$rgb));
        $font = config('beyblade.font_path');
        $white = imagecolorallocate($img, 255, 255, 255);
        if ($font && is_file($font)) {
            imagettftext($img, (int) ($h * 0.3), 0, (int) ($w * 0.32), (int) ($h * 0.62), $white, $font, $label);
        }
        ob_start();
        imagepng($img);
        $out = ob_get_clean();
        imagedestroy($img);

        return $out;
    }
}
