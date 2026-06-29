<?php

namespace App\Events;

use App\Models\Battle;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 對戰更新（計分/狀態變動）→ 即時推播到賽事公開看板頻道，
 * 讓觀眾看板與直播 TV 版面免輪詢即時更新。
 */
class BattleUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $tournamentId;
    public array $payload;

    public function __construct(Battle $battle)
    {
        $battle->loadMissing('playerA', 'playerB', 'stage.division');
        $this->tournamentId = (int) $battle->stage->division->tournament_id;
        $this->payload = [
            'id' => $battle->id,
            'division' => $battle->stage?->division?->name,
            'status' => $battle->status?->value ?? 'pending',
            'stream_url' => $battle->stream_url,
            'a' => ['name' => $battle->playerA?->nickname ?: $battle->playerA?->real_name, 'avatar' => $battle->playerA?->avatar_path, 'score' => $battle->score_a],
            'b' => ['name' => $battle->playerB?->nickname ?: $battle->playerB?->real_name, 'avatar' => $battle->playerB?->avatar_path, 'score' => $battle->score_b],
            'winner_id' => $battle->winner_id,
        ];
    }

    public function broadcastOn(): Channel
    {
        return new Channel("tournament.{$this->tournamentId}");
    }

    public function broadcastAs(): string
    {
        return 'battle.updated';
    }
}
