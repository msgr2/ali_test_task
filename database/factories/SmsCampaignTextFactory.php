<?php

namespace Database\Factories;

use App\Models\SmsCampaignText;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class SmsCampaignTextFactory extends Factory
{
    protected $model = SmsCampaignText::class;

    public function definition(): array
    {
        return [
            'sms_campaign_id' => $this->faker->word(),
            'text' => $this->faker->text(),
            'is_active' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
