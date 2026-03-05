<?php

namespace App\Data\Role;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

class CreateRoleData extends Data
{
    public function __construct(
        #[Required, Max(255), Unique('roles', 'name')]
        public readonly string $name,
    ) {}
}
