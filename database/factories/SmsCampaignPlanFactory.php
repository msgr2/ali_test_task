<?php

namespace Database\Factories;

use App\Models\Offer;
use App\Models\SmsCampaignPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Str;

class SmsCampaignPlanFactory extends Factory
{
    protected $model = SmsCampaignPlan::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'meta' => json_encode([
                'countries' => ['AU'],
                'segment' => 'lists=1,2,3&tags=1,2,3&carrier=1,2,3&max_sent=3',
                'max_budget' => 200,
                'test_budget' => 10,
                'days' => ['mon', 'tue', 'wed', 'thu', 'fri'],
                'max_sms_per_week_per_contact' => 2,
                'hours' => '18:00-22:00',
                'routing_plan' => [1],
                'texts' => [
                    ['text' => 'text1', 'is_active' => true],
                    ['text' => 'text2', 'is_active' => true],
                    ['text' => 'text3', 'is_active' => true]
                ],
                'senderids' => [
                    ['text' => 'sender1', 'is_active' => true],
                    ['text' => 'sender2', 'is_active' => true],
                ],
                'autosender_settings' => [
                    'step_size' => 100,
                    'min_ctr' => 0.04,
                    'optimise_texts' => true,
                    'optimise_sender_ids' => true,
                    'optimise_segments' => true,
                    'optimise_routing_plan' => true,
                    'optimise_hours' => true,
                    'optimise_days' => true,
                    'optimise_countries' => true,
                    'optimise_carriers' => true,
                ],
                'offers' => [
                    [
                        'id' => Offer::factory()->create()->id,
                        'is_active' => true
                    ],
                ],
                'text_by_carrier' => true,
                'auto_expand_texts' => true,
                'notify_slack' => true,
                'sent_to_people_with_timezone_unknown' => true,
            ]),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
