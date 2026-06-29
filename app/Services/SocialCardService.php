<?php

namespace App\Services;

use App\Models\Battle;

/**
 * 社群圖文產生：產出戰果卡圖片（GD + TrueType 中文字型），
 * 供「產生圖文 + 人工發布」到社群/LINE。
 */
class SocialCardService
{
    /** 產生一張對戰戰果卡 PNG，回傳位元組。 */
    public function resultCard(Battle $battle, int $w = 1200, int $h = 630): string
    {
        $battle->loadMissing('playerA', 'playerB', 'stage.division.tournament');

        $img = imagecreatetruecolor($w, $h);
        $c = [
            'bg' => imagecolorallocate($img, 12, 15, 23),
            'gold' => imagecolorallocate($img, 255, 203, 69),
            'white' => imagecolorallocate($img, 238, 242, 255),
            'red' => imagecolorallocate($img, 255, 59, 59),
            'blue' => imagecolorallocate($img, 47, 123, 255),
            'muted' => imagecolorallocate($img, 138, 148, 172),
        ];
        imagefill($img, 0, 0, $c['bg']);
        imagefilledrectangle($img, 0, 0, $w, 10, $c['gold']);

        $font = $this->fontPath();

        $tournament = $battle->stage?->division?->tournament?->name ?? 'Tailtooth';
        $division = $battle->stage?->division?->name ?? '';
        $aName = $battle->playerA?->nickname ?: ($battle->playerA?->real_name ?? 'A');
        $bName = $battle->playerB?->nickname ?: ($battle->playerB?->real_name ?? 'B');

        if ($font) {
            imagettftext($img, 34, 0, 48, 70, $c['gold'], $font, $tournament);
            imagettftext($img, 18, 0, 48, 108, $c['muted'], $font, $division . '　戰果');

            // 雙方名稱
            imagettftext($img, 40, 0, 60, 330, $c['red'], $font, $aName);
            $bw = $this->textWidth(40, $font, $bName);
            imagettftext($img, 40, 0, $w - 60 - $bw, 330, $c['blue'], $font, $bName);

            // 比分（大字）
            imagettftext($img, 150, 0, 200, 470, $c['red'], $font, (string) $battle->score_a);
            imagettftext($img, 40, 0, $w / 2 - 40, 430, $c['muted'], $font, 'VS');
            imagettftext($img, 150, 0, $w - 320, 470, $c['blue'], $font, (string) $battle->score_b);

            // 勝者
            $winner = $battle->winner_id === $battle->player_a_id ? $aName
                : ($battle->winner_id === $battle->player_b_id ? $bName : null);
            if ($winner) {
                imagettftext($img, 30, 0, 60, $h - 50, $c['gold'], $font, '冠軍 WINNER：' . $winner);
            }
            imagettftext($img, 16, 0, $w - 200, $h - 40, $c['muted'], $font, 'TAILTOOTH');
        } else {
            // 無字型時的退化版本（英數）
            imagestring($img, 5, 48, 40, $tournament, $c['gold']);
            imagestring($img, 5, 200, 280, (string) $battle->score_a . ' : ' . $battle->score_b, $c['white']);
        }

        ob_start();
        imagepng($img);
        $out = ob_get_clean();
        imagedestroy($img);

        return $out;
    }

    /** 可用字型路徑，無則回 null（退化為點陣字）。 */
    private function fontPath(): ?string
    {
        $path = config('beyblade.font_path');

        return $path && is_file($path) ? $path : null;
    }

    private function textWidth(int $size, string $font, string $text): int
    {
        $box = imagettfbbox($size, 0, $font, $text);

        return abs($box[2] - $box[0]);
    }
}
