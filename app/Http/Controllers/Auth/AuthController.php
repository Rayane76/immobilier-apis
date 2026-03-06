<?php

namespace App\Http\Controllers\Auth;

use App\Data\Auth\LoginDTO;
use App\Data\Auth\RegisterDTO;
use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;

/**
 * All authentication endpoints are public — no Sanctum token required.
 *
 * @group Authentication
 * @unauthenticated
 */
class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function register(RegisterDTO $data): JsonResponse
    {
        $response = $this->authService->register($data);

        return response()->json($response, 201);
    }

    public function login(LoginDTO $data): JsonResponse
    {
        try {
            $response = $this->authService->login($data);
        } catch (AuthenticationException) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        return response()->json($response);
    }
}
