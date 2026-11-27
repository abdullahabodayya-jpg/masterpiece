<?php

namespace App\Http\Middleware;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated');
        }

        if ($user->role !== UserRole::Admin) {
            abort(403, 'Access denied');

        }

        if ($user->status !== AccountStatus::Active) {
            abort(403, 'Account is not active');
        }

        return $next($request);
    }
}
