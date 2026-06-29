<?php

namespace App\Http\Controllers;

use App\Models\Battle;
use App\Services\NotificationService;
use App\Services\SocialCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * 社群一鍵發布：產生戰果卡圖片 + 建議文案。
 * FB/IG 走「圖文 + 人工發布」（回傳圖片網址與文案）；LINE 官方帳號可直接廣播。
 */
class PublishController extends Controller
{
    public function __construct(
        private SocialCardService $cards,
        private NotificationService $notifier,
    ) {}

    public function battle(Request $request, Battle $battle): JsonResponse
    {
        $battle->loadMissing('playerA', 'playerB', 'stage.division.tournament');

        // 產生並儲存戰果卡
        $png = $this->cards->resultCard($battle);
        $path = "cards/battle-{$battle->id}.png";
        Storage::disk('public')->put($path, $png);

        $a = $battle->playerA?->nickname ?: $battle->playerA?->real_name;
        $b = $battle->playerB?->nickname ?: $battle->playerB?->real_name;
        $caption = sprintf(
            "🔥 %s\n%s %d:%d %s\n#戰鬥陀螺 #Tailtooth #%s",
            $battle->stage?->division?->tournament?->name ?? 'Tailtooth',
            $a, $battle->score_a, $battle->score_b, $b,
            str_replace(' ', '', $battle->stage?->division?->name ?? '')
        );

        $result = [
            'image_url' => Storage::disk('public')->url($path),
            'caption' => $caption,
        ];

        // 選擇性：直接廣播到 LINE 官方帳號
        if (in_array('line', (array) $request->input('channels', []), true)) {
            $log = $this->notifier->lineBroadcast($caption, $battle);
            $result['line'] = $log->status;
        }

        return response()->json($result);
    }
}
