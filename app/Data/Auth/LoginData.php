<?php

namespace App\Data\Auth;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;

class LoginData extends Data
{
    public function __construct(
        #[Required, Email, Max(255)]
        public readonly string $email,

        #[Required, Max(255)]
        public readonly string $password,
    ) {}
}
