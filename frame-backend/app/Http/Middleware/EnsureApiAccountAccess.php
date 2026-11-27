<?php

namespace App\Http\Middleware;

use App\Enums\AccountStatus;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class EnsureApiAccountAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('sanctum');
        $logout = $request->route()?->getActionName() === AuthenticatedSessionController::class.'@destroy';

        if ($user && ! $logout && ($user->trashed() || ! in_array($user->status, [
            AccountStatus::Active,
            AccountStatus::PendingVerification,
        ], true))) {
            $id = (string) Str::uuid();

            return response()->json(['error' => [
                'code' => 'forbidden',
                'message' => 'The account cannot access this operation.',
                'request_id' => $id,
            ]], 403, ['X-Request-ID' => $id, 'Cache-Control' => 'private, no-store']);
        }

        return $next($request);
    }
}
