<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// 賽事看板為公開頻道（觀眾/TV 看板皆可訂閱），不需授權。
// 頻道名稱：tournament.{tournamentId}
