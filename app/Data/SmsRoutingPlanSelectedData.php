<?php

namespace App\Data;

use App\Enums\SmsRoutingPlanRuleActionEnum;
use App\Enums\SmsRoutingPlanSelectedMethodEnum;
use App\Enums\SmsRoutingPlanSelectedStatusEnum;
use App\Models\SmsRoutingPlanRule;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\RequiredIf;
use Spatie\LaravelData\Data;

class SmsRoutingPlanSelectedData extends Data
{
    public function __construct(
        #[Enum(SmsRoutingPlanSelectedStatusEnum::class)]
        public string                      $status,
        #[Enum(SmsRoutingPlanSelectedMethodEnum::class)]
        public string                      $selected_method,
        public string                      $selected_action,
        #[RequiredIf('selected_method', 'rules')]
        public ?SmsRoutingPlanRule         $selected_rule = null,
        #[RequiredIf('selected_method', 'rules')]
        public ?string                     $selected_rule_id = null,
        #[Enum(SmsRoutingPlanRuleActionEnum::class)]
        public ?string                     $selected_route_id = null,
        public ?float                      $route_rate = null,

        public ?SmsRoutingPlanSelectorData $selector_data = null,
    )
    {
    }
}
