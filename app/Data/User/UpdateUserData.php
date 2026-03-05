<?php

namespace App\Data\User;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class UpdateUserData extends Data
{
    public function __construct(
        #[Sometimes, Max(255)]
        public readonly string|Optional $name,

        #[Sometimes, Email, Max(255)]
        public readonly string|Optional $email,

        #[Sometimes, Nullable, Min(8), Max(255)]
        public readonly string|null|Optional $password,
    ) {}
}
