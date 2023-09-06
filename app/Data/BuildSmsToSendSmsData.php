<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class BuildSmsToSendSmsData extends Data
{
    public function __construct(

        public \App\Services\SendingProcess\Data\BuildSmsData $buildSmsData,
    )
    {
    }
}
