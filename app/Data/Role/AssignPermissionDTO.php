<?php

namespace App\Data\Role;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class AssignPermissionDTO extends Data
{
    public function __construct(
        #[Required, Exists('permissions', 'name')]
        public readonly string $permission,
    ) {}
}
