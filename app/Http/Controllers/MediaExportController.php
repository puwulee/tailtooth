<?php

namespace App\Http\Controllers;

use App\Models\Battle;
use App\Models\Tournament;
use App\Services\ArchiveService;
use App\Services\SocialCardService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** 一鍵匯出：社群戰果卡圖片、賽事成績電子檔案。 */
class MediaExportController extends Controller
{
    /** 下載對戰戰果卡 PNG（社群圖文）。 */
    public function battleCard(Battle $battle, SocialCardService $cards): Response
    {
        $png = $cards->resultCard($battle);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="battle-' . $battle->id . '-card.png"',
        ]);
    }

    /** 匯出賽事成績電子檔案：?format=html（預設）或 json。 */
    public function archive(Request $request, Tournament $tournament, ArchiveService $archive): Response
    {
        $results = $archive->tournamentResults($tournament);

        if ($request->query('format') === 'json') {
            return response()->json($results);
        }

        return response($archive->toHtml($results), 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="tournament-' . $tournament->id . '-results.html"',
        ]);
    }
}
