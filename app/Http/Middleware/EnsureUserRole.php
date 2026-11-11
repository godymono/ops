<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ($roles && ! in_array($user->role, $roles, true))) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Only Head Office can perform this action.'], 403);
            }

            abort(403, 'Only Head Office can perform this action.');
        }

        return $next($request);
    }
}
