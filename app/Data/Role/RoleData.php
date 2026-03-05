<?php

namespace App\Data\Role;

use Spatie\Permission\Models\Role;
use Spatie\LaravelData\Data;

class RoleData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        /** @var string[] */
        public readonly array $permissions,
    ) {}

    public static function fromModel(Role $role): self
    {
        return new self(
            id: $role->id,
            name: $role->name,
            permissions: $role->permissions->pluck('name')->toArray(),
        );
    }
}
