<?php

namespace App\Services;

use App\Enums\BattleStatus;
use App\Models\Battle;
use App\Models\Sponsor;
use App\Models\Tournament;

/**
 * 看板資料組裝（即時看板 / 觀眾看板 / 直播 TV 版面共用）。
 */
class BoardService
{
    /** 組裝某賽事的看板資料：進行中、即將開始、最近結果。 */
    public function forTournament(Tournament $tournament): array
    {
        $battles = Battle::query()
            ->whereHas('stage.division', fn ($q) => $q->where('tournament_id', $tournament->id))
            ->with(['playerA', 'playerB', 'winner', 'venue', 'stage.division'])
            ->orderByDesc('updated_at')
            ->get();

        return [
            'tournament' => [
                'name' => $tournament->name,
                'stream_url' => $tournament->stream_url,
                'event_date' => $tournament->event_date?->toDateString(),
            ],
            'live' => $battles->where('status', BattleStatus::InProgress)->map(fn ($b) => $this->present($b))->values(),
            'upcoming' => $battles->where('status', BattleStatus::Pending)->take(8)->map(fn ($b) => $this->present($b))->values(),
            'recent' => $battles->whereIn('status', [BattleStatus::Finished, BattleStatus::Confirmed])
                ->take(8)->map(fn ($b) => $this->present($b))->values(),
            'sponsors' => Sponsor::all()->map(fn ($s) => [
                'name' => $s->name,
                'logo' => $s->logo_path ? asset('storage/' . $s->logo_path) : null,
            ])->values(),
        ];
    }

    private function present(Battle $b): array
    {
        return [
            'id' => $b->id,
            'division' => $b->stage?->division?->name,
            'venue' => $b->venue?->name,
            'status' => $b->status->value,
            'stream_url' => $b->stream_url,
            'a' => ['name' => $b->playerA?->nickname ?: $b->playerA?->real_name, 'avatar' => $b->playerA?->avatar_path, 'score' => $b->score_a],
            'b' => ['name' => $b->playerB?->nickname ?: $b->playerB?->real_name, 'avatar' => $b->playerB?->avatar_path, 'score' => $b->score_b],
            'winner_id' => $b->winner_id,
        ];
    }
}
