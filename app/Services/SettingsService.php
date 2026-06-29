<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

/**
 * 後台設定（API 金鑰等）讀寫；讀取時 DB 設定優先，其次回退 config/env。
 * 平台管理者可於 /admin/settings 自行輸入金鑰，無需改 .env。
 */
class SettingsService
{
    private array $cache = [];

    public function get(string $key, mixed $fallback = null): mixed
    {
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key] ?? $fallback;
        }
        if (! Schema::hasTable('settings')) {
            return $fallback;
        }

        $setting = Setting::where('key', $key)->first();
        $value = $setting?->decrypted_value;
        $this->cache[$key] = $value;

        return ($value === null || $value === '') ? $fallback : $value;
    }

    public function set(string $key, ?string $value, string $group = 'general', bool $isSecret = false): Setting
    {
        unset($this->cache[$key]);

        return Setting::updateOrCreate(
            ['key' => $key],
            [
                'group' => $group,
                'is_secret' => $isSecret,
                'value' => ($isSecret && $value !== null && $value !== '') ? Crypt::encryptString($value) : $value,
            ],
        );
    }

    /** 後台顯示用：secret 遮罩。 */
    public function masked(string $key): ?string
    {
        $v = $this->get($key);
        if ($v === null) {
            return null;
        }

        return strlen($v) <= 4 ? '••••' : ('••••' . substr($v, -4));
    }
}
