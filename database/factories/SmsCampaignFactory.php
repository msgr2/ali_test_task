<?php

namespace Database\Factories;

use App\Models\SmsCampaign;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class SmsCampaignFactory extends Factory
{
    protected $model = SmsCampaign::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'team_id' => Team::factory(),
        ];
    }
}
