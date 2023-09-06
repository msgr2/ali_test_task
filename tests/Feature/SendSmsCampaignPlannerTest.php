<?php

namespace Tests\Feature;

use App\Models\Offer;
use App\Models\SmsCampaignPlan;
use App\Models\User;
use App\Services\CountryService;
use App\Services\SmsCampaignPlanService;
use Carbon\Carbon;
use DateTimeZone;
use Tests\TestCase;

class SendSmsCampaignPlannerTest extends TestCase
{
    public function testCampaignPlannerCreate()
    {
        $user = User::factory()->withPersonalTeam()->create();
        $plan = SmsCampaignPlan::make([
            'name' => 'test',
            'team_id' => $user->currentTeam->id,
        ]);

        $plan->addSettings([
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
        ]);
        $plan->save();

        $this->assertEquals('test', $plan->name);
        $this->assertArrayHasKey('countries', $plan->getSettings());
    }

    public function testCampaignPlanCreatingCampaign()
    {
        self::markTestSkipped();
        $user = User::factory()->withPersonalTeam()->create();
        $plan = SmsCampaignPlan::factory()->create([
            'team_id' => $user->currentTeam->id,
        ]);

        $this->assertEquals(true, true);//continue here
        SmsCampaignPlanService::createCampaignCron($plan);
        $this->assertDatabaseHas('sms_campaigns', ['sms_campaign_plan_id' => $plan->id]);
    }

    public function testDifInHoursPerTimezone()
    {
        $this->assertEquals(CountryService::timeHasPassed('UTC', '2020-01-01 00:00:00'), true);
        $time = Carbon::parse('-1 hour', 'UTC')->format('H:i');
        $this->assertEquals(CountryService::timeHasPassed('UTC', $time), true);
        $time = Carbon::parse('+1 hour', 'UTC')->format('H:i');
        $this->assertEquals(CountryService::timeHasPassed('UTC', $time), false);
    }

    //ideas from copilot
    //create campaign
    //run campaign
    //check campaign
    //pause campaign
    //check campaign
    //change campaign settings
    //run campaign
    //check campaign
    //check campaign stats
    //check campaign stats per offer
    //check campaign stats per text
    //check campaign stats per sender
    //check campaign stats per country
    //check campaign stats per carrier
    //check campaign stats per segment
    //check campaign stats per hour
    //check campaign stats per day
    //check campaign stats per routing
    //check campaign stats per week
    //check campaign stats per month
    //check campaign stats per year
    //check campaign stats per week per offer
    //check campaign stats per month per offer
    //check campaign stats per year per offer
    //check campaign stats per week per text
    //check campaign stats per month per text
    //check campaign stats per year per text
    //check campaign stats per week per sender
    //check campaign stats per month per sender
    //check campaign stats per year per sender
    //check campaign stats per week per country
    //check campaign stats per month per country
    //check campaign stats per year per country
    //check campaign stats per week per carrier
    //check campaign stats per month per carrier
    //check campaign stats per year per carrier
    //check campaign stats per week per segment
    //check campaign stats per month per segment
    //check campaign stats per year per segment
    //check campaign stats per week per hour
    //check campaign stats per month per hour
    //check campaign stats per year per hour
    //check campaign stats per week per day
    //check campaign stats per month per day
    //check campaign stats per year per day
    //check campaign stats per week per routing
    //check campaign stats per month per routing
    //check campaign stats per year per routing
    //check campaign stats per week per week
    //check campaign stats per month per week
    //check campaign stats per year per week
    //check campaign stats per week per month
    //check campaign stats per month per month
    //check campaign stats per year per month
    //check campaign stats per week per year
    //check campaign stats per month per year
}
