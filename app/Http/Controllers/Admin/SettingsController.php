<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 後台設定：平台管理者自行輸入各項 API 金鑰（LINE Pay、光貿、LINE 推播、去背）。
 * secret 值加密儲存，顯示時遮罩。
 */
class SettingsController extends Controller
{
    /** 設定欄位定義：key => [group, secret, label] */
    public const FIELDS = [
        'payment.driver' => ['payment', false, '金流驅動 (manual/linepay)'],
        'linepay.channel_id' => ['payment', false, 'LINE Pay Channel ID'],
        'linepay.channel_secret' => ['payment', true, 'LINE Pay Channel Secret'],
        'invoice.driver' => ['invoice', false, '發票驅動 (null/guangmao)'],
        'invoice.endpoint' => ['invoice', false, '光貿 API 端點'],
        'invoice.merchant_id' => ['invoice', false, '光貿商店代號'],
        'invoice.api_key' => ['invoice', true, '光貿 API Key'],
        'line.channel_access_token' => ['line', true, 'LINE Messaging Channel Access Token'],
        'bg.driver' => ['bg', false, '去背驅動 (null/http)'],
        'bg.endpoint' => ['bg', false, '去背服務端點'],
        'bg.api_key' => ['bg', true, '去背服務 API Key'],
    ];

    public function __construct(private SettingsService $settings) {}

    public function ui()
    {
        return view('admin-settings', ['fields' => self::FIELDS]);
    }

    public function index(): JsonResponse
    {
        $out = [];
        foreach (self::FIELDS as $key => [$group, $secret, $label]) {
            $out[] = [
                'key' => $key, 'group' => $group, 'label' => $label, 'is_secret' => $secret,
                'value' => $secret ? $this->settings->masked($key) : $this->settings->get($key),
                'configured' => $this->settings->get($key) !== null,
            ];
        }

        return response()->json($out);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate(['settings' => ['required', 'array']]);

        foreach ($data['settings'] as $key => $value) {
            if (! isset(self::FIELDS[$key]) || $value === null || $value === '') {
                continue; // 空白不覆寫（避免清掉既有 secret）
            }
            [$group, $secret] = self::FIELDS[$key];
            $this->settings->set($key, (string) $value, $group, $secret);
        }
        AuditService::log($request->user()->id, 'settings.update', null, ['keys' => array_keys($data['settings'])]);

        return response()->json(['ok' => true]);
    }
}
