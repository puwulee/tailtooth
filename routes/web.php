<?php

use App\Models\Battle;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// 裁判計分 SPA（正式環境請套 auth + role:referee 中介層）
Route::get('/referee/{battle}', function (Battle $battle) {
    $battle->load('playerA.beyblades', 'playerB.beyblades');

    return view('referee', [
        'battleId' => $battle->id,
        'beybladeA' => $battle->playerA?->beyblades->first()?->id ?? 'null',
        'beybladeB' => $battle->playerB?->beyblades->first()?->id ?? 'null',
    ]);
})->name('referee');
