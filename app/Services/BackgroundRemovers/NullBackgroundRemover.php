<?php

namespace App\Services\BackgroundRemovers;

use App\Services\Contracts\BackgroundRemover;

/** 開發/未設定金鑰時的安全預設：不去背，原樣回傳。 */
class NullBackgroundRemover implements BackgroundRemover
{
    public function remove(string $imageBytes): string
    {
        return $imageBytes;
    }
}
