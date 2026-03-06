<?php

namespace App\Data;

use App\Models\User;
use Spatie\LaravelData\Data;

class UserDTO extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        /** @var string[] */
        public readonly array $roles,
        /** @var string[] */
        public readonly array $permissions,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            roles: $user->getRoleNames()->toArray(),
            permissions: $user->getAllPermissions()->pluck('name')->toArray(),
        );
    }
}
