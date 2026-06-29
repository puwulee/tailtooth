<?php

namespace App\Services;

use App\Models\Battle;

/**
 * 社群圖文產生：產出戰果卡圖片（GD），供「產生圖文 + 人工發布」到社群/LINE。
 */
class SocialCardService
{
    /** 產生一張對戰戰果卡 PNG，回傳位元組。 */
    public function resultCard(Battle $battle, int $w = 1200, int $h = 630): string
    {
        $battle->loadMissing('playerA', 'playerB', 'stage.division.tournament');

        $img = imagecreatetruecolor($w, $h);
        $bg = imagecolorallocate($img, 12, 15, 23);
        $gold = imagecolorallocate($img, 255, 203, 69);
        $white = imagecolorallocate($img, 238, 242, 255);
        $red = imagecolorallocate($img, 255, 59, 59);
        $blue = imagecolorallocate($img, 47, 123, 255);
        $muted = imagecolorallocate($img, 138, 148, 172);
        imagefill($img, 0, 0, $bg);

        // 頂部金線與標題
        imagefilledrectangle($img, 0, 0, $w, 8, $gold);
        $tournament = $battle->stage?->division?->tournament?->name ?? 'Tailtooth';
        $this->text($img, 5, 40, 30, $gold, $tournament);
        $this->text($img, 3, 40, 70, $muted, ($battle->stage?->division?->name ?? '') . '  ·  戰果');

        $aName = $battle->playerA?->nickname ?: ($battle->playerA?->real_name ?? 'A');
        $bName = $battle->playerB?->nickname ?: ($battle->playerB?->real_name ?? 'B');

        // 比分
        $this->text($img, 5, 120, 280, $red, $aName);
        $this->text($img, 5, $w - 260, 280, $blue, $bName);
        $this->bigScore($img, (string) $battle->score_a, 360, 300, $red);
        $this->text($img, 5, $w / 2 - 20, 320, $muted, 'VS');
        $this->bigScore($img, (string) $battle->score_b, $w - 420, 300, $blue);

        // 勝者
        $winner = $battle->winner_id === $battle->player_a_id ? $aName
            : ($battle->winner_id === $battle->player_b_id ? $bName : null);
        if ($winner) {
            $this->text($img, 5, 40, $h - 70, $gold, '🏆 WINNER: ' . $winner);
        }
        $this->text($img, 3, $w - 220, $h - 40, $muted, 'TAILTOOTH');

        ob_start();
        imagepng($img);
        $out = ob_get_clean();
        imagedestroy($img);

        return $out;
    }

    private function text($img, int $font, int $x, int $y, int $color, string $s): void
    {
        // GD 內建點陣字不支援中文與大字級，這裡以英數/符號為主；
        // 正式版可改用 imagettftext + 中文字型檔（Noto Sans TC）。
        imagestring($img, $font, $x, $y, $s, $color);
    }

    private function bigScore($img, string $s, int $x, int $y, int $color): void
    {
        // 放大數字：以多次描繪模擬大字級
        for ($i = 0; $i < 6; $i++) {
            imagestring($img, 5, $x + $i, $y, $s, $color);
            imagestring($img, 5, $x, $y + $i, $s, $color);
        }
    }
}
