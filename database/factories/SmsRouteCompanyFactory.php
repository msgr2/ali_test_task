<?php

namespace Database\Factories;

use App\Models\SmsRouteCompany;
use Illuminate\Database\Eloquent\Factories\Factory;

class SmsRouteCompanyFactory extends Factory
{
    protected $model = SmsRouteCompany::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
//            'meta' => [
//                'sms_route_smpp_connection_id' => SmsRouteSmppConnection::factory()->create()->id,
//                'sms_routing_plan_id' => SmsRoutingPlan::factory()->create()->id,
//            ]
        ];
    }
}
