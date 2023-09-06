<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class SmsRoutingCompanyData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
    )
    {
    }
}
