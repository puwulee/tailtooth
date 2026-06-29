<?php

use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\AppealController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\Participant\BeybladeController;
use App\Http\Controllers\Participant\ProfileController;
use App\Http\Controllers\PublishController;
use App\Http\Controllers\RefereeScoringController;
use App\Http\Controllers\SchedulingController;
use App\Models\Battle;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));

// 認證
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');

// 公開看板（觀眾/電視，無需登入）
Route::get('/broadcast/{tournament}', [BroadcastController::class, 'tv'])->name('broadcast.tv');
Route::get('/board/{tournament}', [BroadcastController::class, 'board'])->name('broadcast.board');

/*
| 賽務/裁判（裁判、記錄員、主辦、系統、平台）
*/
Route::middleware(['auth', 'role:referee,scorekeeper,organizer,system,platform'])->group(function () {
    Route::get('/referee/{battle}', function (Battle $battle) {
        $battle->load('playerA.beyblades', 'playerB.beyblades');

        return view('referee', [
            'battleId' => $battle->id,
            'beybladeA' => $battle->playerA?->beyblades->first()?->id ?? 'null',
            'beybladeB' => $battle->playerB?->beyblades->first()?->id ?? 'null',
        ]);
    })->name('referee');

    Route::prefix('api/battles/{battle}')->group(function () {
        Route::get('/', [RefereeScoringController::class, 'show']);
        Route::post('/start', [RefereeScoringController::class, 'start']);
        Route::post('/rounds', [RefereeScoringController::class, 'recordRound']);
        Route::post('/draw', [RefereeScoringController::class, 'recordDraw']);
    });

    // 申訴審理（裁判/主辦）
    Route::get('/api/appeals', [AppealController::class, 'index']);
    Route::post('/api/appeals/{appeal}/rule', [AppealController::class, 'rule']);
    Route::get('/admin/appeals', fn () => view('appeals'))->name('appeals.ui');
});

/*
| 賽程編排與發布（主辦、系統、平台）
*/
Route::middleware(['auth', 'role:organizer,system,platform'])->group(function () {
    Route::get('/admin/scheduling/{tournament}', [SchedulingController::class, 'ui'])->name('scheduling.ui');
    Route::get('/api/scheduling/{tournament}/overview', [SchedulingController::class, 'overview']);
    Route::post('/api/scheduling/divisions/{division}/generate', [SchedulingController::class, 'generate']);
    Route::get('/api/scheduling/divisions/{division}/battles', [SchedulingController::class, 'battles']);
    Route::post('/api/scheduling/battles/{battle}/draw-venue', [SchedulingController::class, 'drawVenue']);

    Route::post('/api/publish/battle/{battle}', [PublishController::class, 'battle']);
});

/*
| 後台設定 / API 金鑰（平台、系統）
*/
Route::middleware(['auth', 'role:platform,system'])->group(function () {
    Route::get('/admin/settings', [SettingsController::class, 'ui'])->name('settings.ui');
    Route::get('/api/admin/settings', [SettingsController::class, 'index']);
    Route::post('/api/admin/settings', [SettingsController::class, 'update']);
});

/*
| 參賽者後台：大頭照、陀螺登錄、申訴
*/
Route::middleware('auth')->group(function () {
    Route::post('/api/players/{player}/avatar', [ProfileController::class, 'uploadAvatar']);
    Route::post('/api/players/{player}/beyblades', [BeybladeController::class, 'store']);
    Route::post('/api/battles/{battle}/appeals', [AppealController::class, 'store']);
});
