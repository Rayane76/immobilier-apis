<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * For multipart/form-data requests, PHP transmits every field as a plain
 * string — including fields that the client serialised as a JSON object
 * (e.g. Swagger's "object" input sends {"key":"value"}).
 *
 * This middleware walks all scalar request inputs and, for any value that
 * is a valid JSON object or JSON array, replaces the raw string with the
 * decoded PHP value.  This happens before Laravel's validation and before
 * Spatie Data normalises the request, so every downstream consumer
 * (rules(), DTOs, form-requests) sees the correct type immediately.
 */
class DecodeJsonFormFields
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isMultipart($request)) {
            $decoded = [];

            foreach ($request->all() as $key => $value) {
                $decoded[$key] = $this->maybeDecodeJson($value);
            }

            $request->merge($decoded);
        }

        return $next($request);
    }

    // -------------------------------------------------------------------------

    private function isMultipart(Request $request): bool
    {
        $contentType = $request->header('Content-Type', '');

        return str_contains($contentType, 'multipart/form-data')
            || str_contains($contentType, 'application/x-www-form-urlencoded');
    }

    private function maybeDecodeJson(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $trimmed = ltrim($value);

        // Only attempt JSON decode when the string looks like an object or array
        if (!str_starts_with($trimmed, '{') && !str_starts_with($trimmed, '[')) {
            return $value;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }
}
