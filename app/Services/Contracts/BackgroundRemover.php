<?php

namespace App\Services\Contracts;

interface BackgroundRemover
{
    /**
     * 去背：吃原始圖位元組，回傳去背後（含透明通道）的 PNG 位元組。
     * 失敗或未設定時可回傳原圖，由上層決定 photo_status。
     */
    public function remove(string $imageBytes): string;
}
