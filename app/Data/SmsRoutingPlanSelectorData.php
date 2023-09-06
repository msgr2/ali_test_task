<?php

namespace App\Data;

use App\Models\SmsRoutingPlan;
use Spatie\LaravelData\Data;

class SmsRoutingPlanSelectorData extends Data
{
    public function __construct(
        public string     $country_id,
        public string     $plan_id,
        public string     $team_id,
        public string|int $counter,
        public ?string    $fail_status = null,
        public ?array     $filtered_route_ids = [],
        public ?array     $filtered_rules_ids = [],
    )
    {
    }
}
