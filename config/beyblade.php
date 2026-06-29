<?php

return [
    /*
    | 陀螺照片處理：使用者自行拍照上傳（無官方授權），系統做 AI 去背 →
    | 浮水印 → 統一尺寸，使全站陀螺圖規格一致。
    */
    'photo' => [
        // 統一輸出尺寸（正方形，置中留白），確保排行榜/圖卡視覺一致
        'canvas_width' => (int) env('BEY_PHOTO_W', 800),
        'canvas_height' => (int) env('BEY_PHOTO_H', 800),
        'format' => env('BEY_PHOTO_FORMAT', 'png'),
        'background' => env('BEY_PHOTO_BG', 'transparent'), // transparent | #RRGGBB
    ],

    /*
    | 圖文/浮水印用的中文字型（TTF/TTC）。預設用系統的 WenQuanYi Zen Hei（繁簡通用）。
    | 正式環境請安裝 fonts-wqy-zenhei，或自備字型並設 BEY_FONT_PATH。
    */
    'font_path' => env('BEY_FONT_PATH', '/usr/share/fonts/truetype/wqy/wqy-zenhei.ttc'),

    'watermark' => [
        'text' => env('BEY_WATERMARK', 'Tailtooth'),
        'opacity' => (float) env('BEY_WATERMARK_OPACITY', 0.35),
        'position' => env('BEY_WATERMARK_POS', 'bottom-right'),
    ],

    /*
    | 去背驅動：
    |   null   - 不去背（開發/未設定金鑰時的安全預設，直接沿用原圖）
    |   http   - 呼叫自架 rembg / remove.bg 相容 HTTP 服務
    */
    'background_removal' => [
        'driver' => env('BEY_BG_DRIVER', 'null'),
        'endpoint' => env('BEY_BG_ENDPOINT'),
        'api_key' => env('BEY_BG_API_KEY'),
        'timeout' => (int) env('BEY_BG_TIMEOUT', 30),
    ],
];
