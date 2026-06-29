<?php

namespace App\Services;

use App\Models\Beyblade;
use App\Services\Contracts\BackgroundRemover;
use Illuminate\Support\Facades\Storage;

/**
 * 陀螺照片處理 pipeline：使用者自行上傳 → AI 去背 → 浮水印 → 統一尺寸。
 * 全站陀螺圖輸出為相同畫布尺寸（置中、透明背景），確保排行榜/圖卡視覺一致。
 */
class BeybladePhotoService
{
    public function __construct(private BackgroundRemover $remover) {}

    /**
     * 處理單顆陀螺的原始照片，產生去背圖與浮水印圖，更新 model 路徑與狀態。
     */
    public function process(Beyblade $beyblade, string $disk = 'public'): Beyblade
    {
        $beyblade->update(['photo_status' => 'processing']);

        try {
            $original = Storage::disk($disk)->get($beyblade->photo_path);

            // 1) 去背
            $cutoutBytes = $this->remover->remove($original);
            $cutoutPath = $this->derivedPath($beyblade->photo_path, 'cutout');
            Storage::disk($disk)->put($cutoutPath, $cutoutBytes);

            // 2) 統一尺寸 + 浮水印
            $finalBytes = $this->normalizeAndWatermark($cutoutBytes);
            $finalPath = $this->derivedPath($beyblade->photo_path, 'wm');
            Storage::disk($disk)->put($finalPath, $finalBytes);

            $beyblade->update([
                'cutout_path' => $cutoutPath,
                'watermarked_path' => $finalPath,
                'photo_status' => 'done',
            ]);
        } catch (\Throwable $e) {
            $beyblade->update(['photo_status' => 'failed']);
            throw $e;
        }

        return $beyblade;
    }

    /**
     * 縮放置中到統一畫布並加浮水印，回傳 PNG 位元組。公開以利測試。
     */
    public function normalizeAndWatermark(string $imageBytes): string
    {
        $cfg = config('beyblade.photo');
        $w = $cfg['canvas_width'];
        $h = $cfg['canvas_height'];

        $src = imagecreatefromstring($imageBytes);
        if ($src === false) {
            throw new \RuntimeException('無法解析上傳的圖片');
        }
        $srcW = imagesx($src);
        $srcH = imagesy($src);

        $canvas = imagecreatetruecolor($w, $h);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparent);
        imagealphablending($canvas, true);

        // 等比縮放置中
        $scale = min($w / $srcW, $h / $srcH);
        $dstW = (int) round($srcW * $scale);
        $dstH = (int) round($srcH * $scale);
        $dstX = (int) (($w - $dstW) / 2);
        $dstY = (int) (($h - $dstH) / 2);
        imagecopyresampled($canvas, $src, $dstX, $dstY, 0, 0, $dstW, $dstH, $srcW, $srcH);

        $this->stampWatermark($canvas, $w, $h);

        ob_start();
        imagepng($canvas);
        $out = ob_get_clean();

        imagedestroy($src);
        imagedestroy($canvas);

        return $out;
    }

    private function stampWatermark($canvas, int $w, int $h): void
    {
        $cfg = config('beyblade.watermark');
        $text = $cfg['text'] ?? 'Tailtooth';
        $alpha = (int) round((1 - (float) $cfg['opacity']) * 127);

        $color = imagecolorallocatealpha($canvas, 255, 255, 255, max(0, min(127, $alpha)));
        $font = 5; // GD 內建字型
        $tw = imagefontwidth($font) * strlen($text);
        $th = imagefontheight($font);

        [$x, $y] = match ($cfg['position'] ?? 'bottom-right') {
            'bottom-left' => [10, $h - $th - 10],
            'top-left' => [10, 10],
            'top-right' => [$w - $tw - 10, 10],
            'center' => [(int) (($w - $tw) / 2), (int) (($h - $th) / 2)],
            default => [$w - $tw - 10, $h - $th - 10],
        };

        imagestring($canvas, $font, $x, $y, $text, $color);
    }

    private function derivedPath(string $original, string $suffix): string
    {
        $info = pathinfo($original);
        $dir = $info['dirname'] ?? '';
        $name = $info['filename'] ?? 'photo';

        return ($dir ? $dir . '/' : '') . "{$name}_{$suffix}.png";
    }
}
