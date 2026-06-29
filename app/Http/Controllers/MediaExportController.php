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

    /** 匯出賽事成績電子檔案：?format=html（預設）/ json / pdf。 */
    public function archive(Request $request, Tournament $tournament, ArchiveService $archive): Response
    {
        $results = $archive->tournamentResults($tournament);
        $format = $request->query('format', 'html');

        if ($format === 'json') {
            return response()->json($results);
        }

        if ($format === 'pdf') {
            $pdf = app(\App\Services\PdfService::class)->render($archive->toHtmlBody($results), $tournament->name . ' 成績');

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="tournament-' . $tournament->id . '-results.pdf"',
            ]);
        }

        return response($archive->toHtml($results), 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="tournament-' . $tournament->id . '-results.html"',
        ]);
    }
}
