<?php

namespace App\Data\Role;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class UpdateRoleDTO extends Data
{
    public function __construct(
        #[Sometimes, Max(255)]
        public readonly string|Optional $name,
    ) {}
}
