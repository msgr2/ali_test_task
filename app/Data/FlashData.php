<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

/** @typescript */
class FlashData extends Data
{
    public function __construct(
        /* @enum('success') */
        public string          $type,
        public string          $title,
        public string|Optional $message,
    )
    {
    }

    public static function createToUi(string $type, string $title, string $message): array
    {
        return (new self($type, $title, $message))->toArray();
    }
}
