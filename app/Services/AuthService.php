<?php

namespace App\Services;

use App\Data\Auth\AuthResponseDTO;
use App\Data\Auth\LoginDTO;
use App\Data\Auth\RegisterDTO;
use App\Data\UserDTO;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * @throws AuthenticationException
     */
    public function login(LoginDTO $data): AuthResponseDTO
    {
        $user = $this->userRepository->findByEmail($data->email);

        if (!$user || !Hash::check($data->password, $user->password)) {
            throw new AuthenticationException('Invalid credentials.');
        }

        return $this->buildAuthResponse($user);
    }

    public function register(RegisterDTO $data): AuthResponseDTO
    {
        $user = $this->userRepository->create($data);

        $user->assignRole('visiteur');

        return $this->buildAuthResponse($user);
    }

    /**
     * Builds the unified auth response: token + user + roles + permissions.
     *
     * Permissions are returned in the payload for frontend UI gating only.
     * The backend always re-validates via Policies — this is the source of truth.
     */
    private function buildAuthResponse(User $user): AuthResponseDTO
    {
        // Revoke previous tokens to enforce single-session (remove if multi-device is needed)
        $user->tokens()->delete();

        $token = $user->createToken('api')->plainTextToken;

        return new AuthResponseDTO(
            token: $token,
            user: UserDTO::fromModel($user),
            roles: $user->getRoleNames()->toArray(),
            permissions: $user->getAllPermissions()->pluck('name')->toArray(),
        );
    }
}
