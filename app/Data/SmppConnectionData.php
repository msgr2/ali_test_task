<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Data;

/** @typescript */
class SmppConnectionData extends Data
{
    public function __construct(
        public string  $url,
        #[Between(1, 65535)]
        public int     $port,
        public string  $username,
        public string  $password,
        public ?string $dlrUrl,
        #[Between(1, 65535)]
        public ?int    $dlrPort,
        public ?bool   $is_tested = false,
    )
    {
    }
}
