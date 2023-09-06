<?php

namespace Database\Factories;

use App\Models\SmsRoutingPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class SmsRoutingPlanFactory extends Factory
{
    protected $model = SmsRoutingPlan::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
        ];
    }
}
