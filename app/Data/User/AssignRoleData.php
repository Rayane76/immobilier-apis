<?php

namespace App\Data\User;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class AssignRoleData extends Data
{
    public function __construct(
        #[Required, Exists('roles', 'name')]
        public readonly string $role,
    ) {}
}
