<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Services\BeybladeStatsService;
use App\Services\LeaderboardService;
use App\Services\PlayerProfileService;
use Illuminate\Http\JsonResponse;

/** 公開排行：賽季積分榜、最強陀螺組合榜、選手檔案。 */
class RankingController extends Controller
{
    public function index()
    {
        return view('rankings');
    }

    public function data(LeaderboardService $lb, BeybladeStatsService $stats): JsonResponse
    {
        return response()->json([
            'players' => $lb->seasonRanking(20),
            'combos' => $stats->comboLeaderboard(15),
        ]);
    }

    public function player(Player $player)
    {
        return view('player', ['playerId' => $player->id, 'name' => $player->nickname ?: $player->real_name]);
    }

    public function playerData(Player $player, PlayerProfileService $svc): JsonResponse
    {
        return response()->json($svc->profile($player));
    }
}
