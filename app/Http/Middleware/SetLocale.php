<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/** 多語系：?lang 切換並記憶於 session（zh_TW 預設 / en）。 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->query('lang'), ['zh_TW', 'en'], true)) {
            $request->session()->put('locale', $request->query('lang'));
        }
        App::setLocale($request->session()->get('locale', 'zh_TW'));

        return $next($request);
    }
}
