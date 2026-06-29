<?php

namespace App\Services\Line;

use App\Services\Contracts\LineProfileFetcher;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Http;

/** 串接 LINE Login v2.1：code → token → 解析 id_token 取得使用者資料。 */
class HttpLineProfileFetcher implements LineProfileFetcher
{
    public function __construct(private SettingsService $settings) {}

    public function fetch(string $code, string $redirectUri): array
    {
        $token = Http::asForm()->post('https://api.line.me/oauth2/v2.1/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'client_id' => $this->settings->get('line.login_channel_id'),
            'client_secret' => $this->settings->get('line.login_channel_secret'),
        ])->throw()->json();

        // id_token 為 JWT，payload 內含 sub(userId)/name/picture/email
        $parts = explode('.', $token['id_token'] ?? '');
        $payload = isset($parts[1]) ? json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true) : [];

        return [
            'userId' => $payload['sub'] ?? '',
            'displayName' => $payload['name'] ?? null,
            'pictureUrl' => $payload['picture'] ?? null,
            'email' => $payload['email'] ?? null,
        ];
    }
}
