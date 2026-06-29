<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** 角色授權中介層：route middleware 'role:referee,scorekeeper'。 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (! $user || ! $user->hasAnyRole(...$roles)) {
            abort(403, '權限不足');
        }

        return $next($request);
    }
}
