<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Services\BoardService;
use Illuminate\Http\JsonResponse;

class BroadcastController extends Controller
{
    public function __construct(private BoardService $board) {}

    /** 直播 TV 版面（1920×1080）：左賽程、右直播。 */
    public function tv(Tournament $tournament)
    {
        return view('broadcast', ['tournamentId' => $tournament->id, 'name' => $tournament->name]);
    }

    /** 觀眾看板（現場/網頁，可輪詢）。 */
    public function board(Tournament $tournament)
    {
        return view('board', ['tournamentId' => $tournament->id, 'name' => $tournament->name]);
    }

    /** 看板資料 JSON（供 TV/觀眾看板輪詢）。 */
    public function data(Tournament $tournament): JsonResponse
    {
        return response()->json($this->board->forTournament($tournament));
    }
}
