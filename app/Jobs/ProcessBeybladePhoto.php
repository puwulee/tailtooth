<?php

namespace App\Jobs;

use App\Models\Beyblade;
use App\Services\BeybladePhotoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * 非同步處理陀螺照片（去背 → 浮水印 → 統一尺寸）。
 */
class ProcessBeybladePhoto implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(public int $beybladeId, public string $disk = 'public') {}

    public function handle(BeybladePhotoService $service): void
    {
        $beyblade = Beyblade::find($this->beybladeId);
        if ($beyblade && $beyblade->photo_path) {
            $service->process($beyblade, $this->disk);
        }
    }
}
