<?php

use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\MediaExportController;
use Illuminate\Support\Facades\Route;

/*
| 公開（唯讀）端點：看板資料、成績匯出、戰果卡。無需登入。
| 需身分/權限的端點（裁判計分、賽程編排、設定、上傳、申訴）定義於 routes/web.php，
| 走 session（web 守衛）+ role 中介層。
*/
Route::get('/broadcast/{tournament}/data', [BroadcastController::class, 'data']);
Route::get('/broadcast/{tournament}/archive', [MediaExportController::class, 'archive']);
Route::get('/battles/{battle}/card', [MediaExportController::class, 'battleCard']);
