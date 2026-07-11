<?php

namespace App\Http\Middleware;

use App\Http\Responses\Envelope;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Replay protection for mutating endpoints.
 *
 * Clients send an Idempotency-Key header; the first response (non-5xx) is
 * stored for 24h and replayed verbatim on retries with the same key. Reusing
 * a key with a different payload is a 409. Server errors are not stored so
 * the client may retry them.
 */
class EnsureIdempotency
{
    public const HEADER = 'Idempotency-Key';

    public const REPLAY_HEADER = 'Idempotency-Replay';

    private const TTL_HOURS = 24;

    private const LOCK_WAIT_SECONDS = 10;

    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header(self::HEADER);

        if (! is_string($key) || $key === '' || ! $this->isMutating($request)) {
            return $next($request);
        }

        if (mb_strlen($key) > 255) {
            return Envelope::error('Idempotency-Key must not exceed 255 characters.', [], 422);
        }

        $cacheKey = $this->cacheKey($request, $key);
        $requestHash = $this->requestHash($request);

        $lock = Cache::lock("{$cacheKey}:lock", self::LOCK_WAIT_SECONDS + 5);

        if (! $lock->block(self::LOCK_WAIT_SECONDS)) {
            return Envelope::error('A request with this Idempotency-Key is already in progress.', [], 409);
        }

        try {
            $stored = Cache::get($cacheKey);

            if (is_array($stored)) {
                if (! hash_equals($stored['request_hash'], $requestHash)) {
                    return Envelope::error('This Idempotency-Key was already used with a different request.', [], 409);
                }

                return response($stored['content'], $stored['status'])
                    ->withHeaders($stored['headers'] + [self::REPLAY_HEADER => 'true']);
            }

            $response = $next($request);

            if ($response->getStatusCode() < 500) {
                Cache::put($cacheKey, [
                    'request_hash' => $requestHash,
                    'status' => $response->getStatusCode(),
                    'headers' => ['Content-Type' => $response->headers->get('Content-Type')],
                    'content' => $response->getContent(),
                ], now()->addHours(self::TTL_HOURS));
            }

            return $response;
        } finally {
            $lock->release();
        }
    }

    private function isMutating(Request $request): bool
    {
        return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    private function cacheKey(Request $request, string $key): string
    {
        $subject = $request->user()?->getAuthIdentifier() ?? 'guest:'.$request->ip();

        return 'idempotency:'.$subject.':'.hash('sha256', $key);
    }

    private function requestHash(Request $request): string
    {
        return hash('sha256', $request->method().'|'.$request->path().'|'.$request->getContent());
    }
}
