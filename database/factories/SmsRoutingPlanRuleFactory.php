<?php

namespace Database\Factories;

use App\Enums\SmsRoutingPlanRuleActionEnum;
use App\Models\SmsRoutingPlanRule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class SmsRoutingPlanRuleFactory extends Factory
{
    protected $model = SmsRoutingPlanRule::class;

    public function definition(): array
    {
        return [
            'sms_route_id' => $this->faker->uuid(),
            'sms_routing_plan_id' => $this->faker->word(),
//            'country_id' => null,
//            'network_id' => null,
            'is_active' => true,
            'priority' => 0,
            'action' => SmsRoutingPlanRuleActionEnum::send(),
//            'action_vars' => $this->faker->words(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
