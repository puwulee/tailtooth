<?php

namespace App\Services;

use App\Models\NotificationLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

/**
 * 通知發送：LINE 官方帳號推播（Messaging API）與 Email。
 * LINE channel access token 由後台設定輸入（settings: line.channel_access_token）。
 */
class NotificationService
{
    public function __construct(private SettingsService $settings) {}

    /** LINE 推播給單一 userId。 */
    public function linePush(string $to, string $text, ?Model $related = null): NotificationLog
    {
        $log = $this->log('line', $to, null, $text, $related);
        $token = $this->settings->get('line.channel_access_token');

        if (! $token) {
            // 未設定金鑰：僅記錄（後台輸入金鑰後即生效）
            $log->update(['status' => 'queued', 'error' => '尚未設定 LINE channel access token']);

            return $log;
        }

        try {
            Http::withToken($token)->asJson()->post('https://api.line.me/v2/bot/message/push', [
                'to' => $to,
                'messages' => [['type' => 'text', 'text' => $text]],
            ])->throw();
            $log->update(['status' => 'sent']);
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error' => $e->getMessage()]);
        }

        return $log;
    }

    /** LINE 廣播（給所有好友）— 用於賽事公告。 */
    public function lineBroadcast(string $text, ?Model $related = null): NotificationLog
    {
        $log = $this->log('line', 'broadcast', null, $text, $related);
        $token = $this->settings->get('line.channel_access_token');

        if (! $token) {
            $log->update(['error' => '尚未設定 LINE channel access token']);

            return $log;
        }

        try {
            Http::withToken($token)->asJson()->post('https://api.line.me/v2/bot/message/broadcast', [
                'messages' => [['type' => 'text', 'text' => $text]],
            ])->throw();
            $log->update(['status' => 'sent']);
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error' => $e->getMessage()]);
        }

        return $log;
    }

    public function email(string $to, string $subject, string $body, ?Model $related = null): NotificationLog
    {
        $log = $this->log('email', $to, $subject, $body, $related);

        try {
            Mail::raw($body, fn ($m) => $m->to($to)->subject($subject));
            $log->update(['status' => 'sent']);
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error' => $e->getMessage()]);
        }

        return $log;
    }

    private function log(string $channel, ?string $to, ?string $subject, string $body, ?Model $related): NotificationLog
    {
        return NotificationLog::create([
            'channel' => $channel,
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'status' => 'queued',
            'related_type' => $related ? $related::class : null,
            'related_id' => $related?->getKey(),
        ]);
    }
}
