<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

// ---------------------------------------------------------------------------
// Unified API response schema
//
//  All JSON responses from this API follow this shape:
//
//  Success  →  determined by the controller (2xx)
//
//  Error    →  {
//                  "message": "Human-readable summary.",
//                  "errors":  { "field": ["detail"] }   ← only on validation
//              }
//
// HTTP status codes:
//   400  Bad Request          – malformed input not caught by validation
//   401  Unauthorized         – not authenticated
//   403  Forbidden            – authenticated but not authorised
//   404  Not Found            – model or route not found
//   422  Unprocessable Entity – validation / DB trigger constraint violated
//   500  Internal Server Error
// ---------------------------------------------------------------------------

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Handle CORS before any routing so preflight OPTIONS requests
        // are answered immediately without hitting auth or policy layers.
        $middleware->prepend(\Illuminate\Http\Middleware\HandleCors::class);

        // Decode JSON-serialised strings (e.g. objects sent via Swagger's
        // multipart/form-data "object" fields) before validation runs.
        $middleware->appendToGroup('api', \App\Http\Middleware\DecodeJsonFormFields::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // Only intercept requests that expect a JSON response (API clients).
        // Non-JSON requests (web, CLI) keep Laravel's default behaviour.

        // 1. Validation errors – 422 with per-field breakdown
        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => $e->getMessage(),
                'errors'  => $e->errors(),
            ], 422);
        });

        // 2. PostgreSQL trigger check_violation (SQLSTATE 23514)
        //    Raised by our validate_property_attributes() trigger.
        //    Treated as a validation error from the client's perspective.
        $exceptions->render(function (QueryException $e, Request $request) {
            if (! $request->expectsJson() || $e->getCode() !== '23514') {
                return null;
            }

            // Raw PDO message:
            // "SQLSTATE[23514]: Check violation: 7 ERROR:  <our message>\nDETAIL: ..."
            // Extract only the text after "ERROR:" up to any trailing "DETAIL" line.
            $raw     = $e->getPrevious()?->getMessage() ?? $e->getMessage();
            $message = 'Attribute validation failed.'; // safe fallback

            if (preg_match('/ERROR:\s+(.+?)(?:\nDETAIL|$)/si', $raw, $matches)) {
                $message = trim($matches[1]);
            }

            return response()->json(['message' => $message], 422);
        });

        // 3. Unauthenticated – 401
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json(['message' => 'Unauthenticated.'], 401);
        });

        // 4. Unauthorised – 403
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => $e->getMessage() ?: 'This action is unauthorized.',
            ], 403);
        });

        // 5. Model not found – 404 with a readable model name
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            // \App\Models\PropertyType  →  "PropertyType"
            $model = class_basename($e->getModel());

            return response()->json([
                'message' => "{$model} not found.",
            ], 404);
        });

        // 6. Generic HTTP exceptions (abort(404), abort(503), etc.) – pass through
        //    status code and message as-is.
        $exceptions->render(function (HttpException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => $e->getMessage() ?: 'An error occurred.',
            ], $e->getStatusCode());
        });

        // 7. Anything else – 500, intentionally vague in production
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => app()->hasDebugModeEnabled()
                    ? $e->getMessage()
                    : 'An unexpected error occurred.',
            ], 500);
        });
    })->create();
