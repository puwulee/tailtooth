<?php

namespace App\Http\Controllers;

use App\Models\Tournament;

class HomeController extends Controller
{
    /** 前台首頁：賽事資訊與報名入口。 */
    public function index()
    {
        $tournaments = Tournament::whereIn('status', ['open', 'ongoing', 'draft'])
            ->with('divisions')->latest('event_date')->get();

        return view('home', ['tournaments' => $tournaments]);
    }

    /** 報名頁（需登入）。 */
    public function register(Tournament $tournament)
    {
        $tournament->load('divisions');

        return view('register', ['tournament' => $tournament]);
    }

    /** 參賽者「我的後台」。 */
    public function dashboard()
    {
        $player = auth()->user()->player;

        return view('me', ['player' => $player]);
    }
}
