<?php

use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\Participant\BeybladeController;
use App\Http\Controllers\Participant\ProfileController;
use App\Http\Controllers\RefereeScoringController;
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

// 看板資料（供直播 TV / 觀眾看板輪詢）
Route::get('/broadcast/{tournament}/data', [BroadcastController::class, 'data']);

// 參賽者後台上傳（正式環境套 auth:sanctum；player 應綁定為登入者本人）
Route::prefix('players/{player}')->group(function () {
    Route::post('/avatar', [ProfileController::class, 'uploadAvatar']);   // 大頭照
    Route::post('/beyblades', [BeybladeController::class, 'store']);      // 陀螺登錄（含自拍照）
});
