<?php

namespace App\Services\BackgroundRemovers;

use App\Services\Contracts\BackgroundRemover;
use Illuminate\Support\Facades\Http;

/**
 * 呼叫自架 rembg / remove.bg 相容 HTTP 服務做 AI 去背。
 * endpoint 接受 multipart 欄位 image_file，回傳去背 PNG。
 */
class HttpBackgroundRemover implements BackgroundRemover
{
    public function __construct(
        private string $endpoint,
        private ?string $apiKey = null,
        private int $timeout = 30,
    ) {}

    public function remove(string $imageBytes): string
    {
        $request = Http::timeout($this->timeout)
            ->attach('image_file', $imageBytes, 'upload.png');

        if ($this->apiKey) {
            $request = $request->withHeaders(['X-Api-Key' => $this->apiKey]);
        }

        $response = $request->post($this->endpoint);
        $response->throw();

        return $response->body();
    }
}
