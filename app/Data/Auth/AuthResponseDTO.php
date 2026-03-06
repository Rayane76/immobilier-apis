<?php

namespace App\Data\Auth;

use App\Data\UserDTO;
use Spatie\LaravelData\Data;

class AuthResponseDTO extends Data
{
    public function __construct(
        public readonly string $token,
        public readonly UserDTO $user,
        /** @var string[] */
        public readonly array $roles,
        /** @var string[] */
        public readonly array $permissions,
    ) {}
}
