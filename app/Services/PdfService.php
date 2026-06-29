<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * HTML → PDF（dompdf）。內嵌 CJK 字型確保中文正常顯示。
 */
class PdfService
{
    /** CJK 字型路徑（IPA Gothic，涵蓋常用漢字）。 */
    private function fontPath(): string
    {
        return config('beyblade.pdf_font_path', '/usr/share/fonts/opentype/ipafont-gothic/ipag.ttf');
    }

    public function render(string $bodyHtml, string $title = ''): string
    {
        $fontDir = storage_path('app/fonts');
        if (! is_dir($fontDir)) {
            @mkdir($fontDir, 0775, true);
        }

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('fontDir', $fontDir);
        $options->set('fontCache', $fontDir);
        $options->setChroot(['/', $fontDir]);

        $font = 'file://' . $this->fontPath();
        $html = "<!doctype html><html><head><meta charset='utf-8'><style>"
            . "@font-face{font-family:cjk;font-style:normal;font-weight:normal;src:url('{$font}') format('truetype');}"
            . "*{font-family:cjk,sans-serif;} body{padding:24px;color:#111;} h1{font-size:22px;} h2{font-size:16px;margin-top:18px;} "
            . "li{margin:3px 0;} .muted{color:#666;font-size:12px;}"
            . "</style><title>" . e($title) . "</title></head><body>{$bodyHtml}</body></html>";

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
