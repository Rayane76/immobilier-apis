<?php

namespace App\Data\Auth;

use App\Data\UserData;
use Spatie\LaravelData\Data;

class AuthResponseData extends Data
{
    public function __construct(
        public readonly string $token,
        public readonly UserData $user,
        /** @var string[] */
        public readonly array $roles,
        /** @var string[] */
        public readonly array $permissions,
    ) {}
}
