<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * 賽務操作存證 — 以 hash 串接前一筆，形成防竄改鏈。
 */
class AuditService
{
    public static function log(?int $userId, string $action, ?Model $subject = null, array $payload = []): AuditLog
    {
        $prev = AuditLog::query()->latest('id')->value('hash');

        $log = new AuditLog([
            'user_id' => $userId,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'payload' => $payload,
        ]);
        $log->created_at = now();

        $log->hash = hash('sha256', ($prev ?? '') . '|' . $action . '|' .
            ($log->subject_type ?? '') . '|' . ($log->subject_id ?? '') . '|' .
            json_encode($payload) . '|' . $log->created_at);

        $log->save();

        return $log;
    }
}
