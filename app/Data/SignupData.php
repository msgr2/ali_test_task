<?php

namespace App\Data;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Password;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Data;

class SignupData extends Data
{
    public function __construct(
        public string $name,
        #[Email]
        public string $email,
        #[Regex('/^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/i')]
        public string $website,
        #[Password(min: 6)]
        public string $password,
        public string $company_name,
        public bool   $tc_confirm,
    )
    {
    }
}
