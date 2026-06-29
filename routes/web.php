<?php

use App\Http\Controllers\BroadcastController;
use App\Models\Battle;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// 直播 TV 版面（左賽程、右直播，1920×1080 全螢幕投電視）與觀眾看板
Route::get('/broadcast/{tournament}', [BroadcastController::class, 'tv'])->name('broadcast.tv');
Route::get('/board/{tournament}', [BroadcastController::class, 'board'])->name('broadcast.board');

// 裁判計分 SPA（正式環境請套 auth + role:referee 中介層）
Route::get('/referee/{battle}', function (Battle $battle) {
    $battle->load('playerA.beyblades', 'playerB.beyblades');

    return view('referee', [
        'battleId' => $battle->id,
        'beybladeA' => $battle->playerA?->beyblades->first()?->id ?? 'null',
        'beybladeB' => $battle->playerB?->beyblades->first()?->id ?? 'null',
    ]);
})->name('referee');
