<?php

namespace App\Services;

use App\Models\Player;
use Illuminate\Support\Facades\Storage;

/**
 * 選手大頭照處理：置中裁切為正方形並統一尺寸（不去背、不浮水印，因為是人像）。
 */
class PlayerPhotoService
{
    public function processAvatar(Player $player, string $disk = 'public'): Player
    {
        $bytes = Storage::disk($disk)->get($player->avatar_path);
        $normalized = $this->squareCrop($bytes, (int) config('beyblade.photo.canvas_width', 800));

        $path = preg_replace('/\.\w+$/', '', $player->avatar_path) . '_avatar.png';
        Storage::disk($disk)->put($path, $normalized);
        $player->update(['avatar_path' => $path]);

        return $player;
    }

    /** 置中正方形裁切並縮放到 size×size，回傳 PNG。公開以利測試。 */
    public function squareCrop(string $imageBytes, int $size): string
    {
        $src = imagecreatefromstring($imageBytes);
        if ($src === false) {
            throw new \RuntimeException('無法解析大頭照');
        }
        $w = imagesx($src);
        $h = imagesy($src);
        $side = min($w, $h);
        $sx = (int) (($w - $side) / 2);
        $sy = (int) (($h - $side) / 2);

        $canvas = imagecreatetruecolor($size, $size);
        imagecopyresampled($canvas, $src, 0, 0, $sx, $sy, $size, $size, $side, $side);

        ob_start();
        imagepng($canvas);
        $out = ob_get_clean();

        imagedestroy($src);
        imagedestroy($canvas);

        return $out;
    }
}
