<?php

use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\MediaExportController;
use App\Http\Controllers\Participant\BeybladeController;
use App\Http\Controllers\Participant\ProfileController;
use App\Http\Controllers\RefereeScoringController;
use App\Http\Controllers\SchedulingController;
use Illuminate\Support\Facades\Route;

/*
| 裁判計分 API（供 SPA 呼叫）。正式環境請套上 Sanctum + referee 角色中介層：
|   Route::middleware(['auth:sanctum', 'role:referee,scorekeeper'])
*/
Route::prefix('battles/{battle}')->group(function () {
    Route::get('/', [RefereeScoringController::class, 'show']);
    Route::post('/start', [RefereeScoringController::class, 'start']);
    Route::post('/rounds', [RefereeScoringController::class, 'recordRound']);
    Route::post('/draw', [RefereeScoringController::class, 'recordDraw']);
});

// 看板資料（供直播 TV / 觀眾看板輪詢，備援即時推播）
Route::get('/broadcast/{tournament}/data', [BroadcastController::class, 'data']);
Route::get('/broadcast/{tournament}/archive', [MediaExportController::class, 'archive']); // 一鍵匯出成績
Route::get('/battles/{battle}/card', [MediaExportController::class, 'battleCard']);        // 一鍵匯出戰果卡

// 賽程編排後台 API
Route::get('/scheduling/{tournament}/overview', [SchedulingController::class, 'overview']);
Route::post('/scheduling/divisions/{division}/generate', [SchedulingController::class, 'generate']);
Route::get('/scheduling/divisions/{division}/battles', [SchedulingController::class, 'battles']);
Route::post('/scheduling/battles/{battle}/draw-venue', [SchedulingController::class, 'drawVenue']);

// 參賽者後台上傳（正式環境套 auth:sanctum；player 應綁定為登入者本人）
Route::prefix('players/{player}')->group(function () {
    Route::post('/avatar', [ProfileController::class, 'uploadAvatar']);   // 大頭照
    Route::post('/beyblades', [BeybladeController::class, 'store']);      // 陀螺登錄（含自拍照）
});
