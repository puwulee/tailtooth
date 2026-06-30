<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /** 首頁：輸入活動代碼加入。 */
    public function index()
    {
        return view('home');
    }

    /** 以代碼加入活動。 */
    public function join(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:12'],
        ]);

        $event = Event::where('code', strtoupper(trim($data['code'])))->first();

        if (! $event) {
            return back()->withInput()->withErrors(['code' => '找不到這個活動代碼，請再確認一次。']);
        }

        return redirect()->route('events.show', $event);
    }
}
