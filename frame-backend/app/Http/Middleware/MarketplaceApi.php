<?php

namespace App\Http\Middleware;

use App\Services\Marketplace\MarketplaceAccess;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class MarketplaceApi
{
    public function __construct(private readonly MarketplaceAccess $access) {}

    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');
        if ($viewer = $request->user('sanctum')) {
            // A signed-in account must satisfy the module eligibility policy even on public routes.
            try {
                $this->access->actor($viewer);
            } catch (HttpException $exception) {
                return $this->decorate(response()->json([], $exception->getStatusCode()));
            }
        }

        return $this->decorate($next($request));
    }

    private function decorate(Response $response): Response
    {
        $id = (string) Str::uuid();
        $status = $response->getStatusCode();
        $error = match ($status) {
            400 => ['invalid_request', 'Check the request parameters.'],
            401 => ['authentication_required', 'Sign in to continue.'],
            403 => ['forbidden', 'You cannot perform this action.'],
            404 => ['not_found', 'The requested resource is unavailable.'],
            409 => ['state_conflict', 'The current state does not allow this action.'],
            422 => ['validation_failed', 'Check the request fields.'],
            429 => ['rate_limited', 'Too many requests.'],
            500 => ['internal_error', 'The request could not be completed.'],
            503 => ['temporarily_unavailable', 'Try again later.'],
            default => null,
        };

        if ($error) {
            $body = ['code' => $error[0], 'message' => $error[1], 'request_id' => $id];
            if (in_array($status, [400, 422], true) && $response instanceof JsonResponse) {
                $body['fields'] = $response->getData(true)['errors'] ?? [];
            }
            $headers = $response->headers->all();
            unset($headers['content-type'], $headers['content-length']);
            $response = response()->json(['error' => $body], $status, $headers);
        }

        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Request-ID', $id);

        return $response;
    }
}
