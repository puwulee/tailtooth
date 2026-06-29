<?php

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
