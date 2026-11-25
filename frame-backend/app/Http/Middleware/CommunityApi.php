<?php

namespace App\Http\Middleware;

use App\Services\Community\CommunityAccess;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class CommunityApi
{
    public function __construct(private readonly CommunityAccess $access) {}

    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');
        $viewer = $request->user('sanctum');

        try {
            if ($viewer) {
                $this->access->actor($viewer);
            }
            $response = $next($request);
        } catch (HttpException $exception) {
            $response = response()->json(['message' => $exception->getMessage()], $exception->getStatusCode(), $exception->getHeaders());
        }

        $status = $response->getStatusCode();
        $requestId = (string) Str::uuid();
        $errors = [
            400 => ['invalid_request', 'The request is invalid.'],
            401 => ['authentication_required', 'Sign in to continue.'],
            403 => ['forbidden', 'You cannot perform this action.'],
            404 => ['not_found', 'The requested resource is unavailable.'],
            409 => ['state_conflict', 'The current state does not allow this action.'],
            419 => ['csrf_failed', 'Refresh your session and try again.'],
            422 => ['validation_failed', 'Check the request fields.'],
            429 => ['rate_limited', 'Too many requests.'],
            500 => ['internal_error', 'The request could not be completed.'],
            503 => ['temporarily_unavailable', 'Try again later.'],
        ];

        if (isset($errors[$status])) {
            [$code, $message] = $errors[$status];
            $error = ['code' => $code, 'message' => $message, 'request_id' => $requestId];
            if (in_array($status, [400, 422], true) && $response instanceof JsonResponse) {
                $fields = $response->getData(true)['errors'] ?? [];
                if ($fields !== []) {
                    $error['fields'] = $fields;
                }
            }
            $headers = $response->headers->all();
            unset($headers['content-type'], $headers['content-length']);
            $response = response()->json(['error' => $error], $status, $headers);
        }

        $response->headers->set('X-Request-ID', $requestId);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
