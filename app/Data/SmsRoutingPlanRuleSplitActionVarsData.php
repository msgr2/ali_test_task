<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\AcceptedIf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\Optional;

class SmsRoutingPlanRuleSplitActionVarsData extends Data
{
    public function __construct(
        public array        $route_ids,
        public int|Optional $limit,
    )
    {
    }
}
