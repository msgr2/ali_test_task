<?php

namespace Database\Factories;

use App\Models\SmsCampaignSenderid;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class SmsCampaignSenderidFactory extends Factory
{
    protected $model = SmsCampaignSenderid::class;

    public function definition(): array
    {
        return [
            'sms_campaign_id' => $this->faker->uuid(),
            'text' => $this->faker->text(8),
            'is_active' => true,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
