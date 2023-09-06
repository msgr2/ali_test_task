<?php

namespace Database\Factories;

use App\Data\SmsCampaignSettingsData;
use App\Models\Clickhouse\Contact;
use App\Models\Lists;
use App\Models\Offer;
use App\Models\SmsCampaign;
use App\Models\SmsCampaignSenderid;
use App\Models\SmsCampaignText;
use App\Models\SmsRoute;
use App\Models\SmsRoutingPlan;
use App\Models\User;
use App\Services\BalanceService;
use App\Services\CountryService;
use Illuminate\Database\Eloquent\Factories\Factory;

class SendSmsCampaignFactory extends Factory
{
    public function definition(): array
    {
        return [

        ];
    }

    public function withBasicSetup($contactsCount = 10)
    {
        $user = User::factory()->withPersonalTeam()->create();

        $plan = SmsRoutingPlan::factory()->create([
            'team_id' => $user->currentTeam->id,
            'name' => 'Test plan',
        ]);
        BalanceService::addBalance($user->current_team_id, 1000, []);
        $route1 = SmsRoute::factory()->withRouteRates()->withSmppConnection()->create([
            'team_id' => $user->currentTeam->id,
        ]);
        $contacts = Contact::factory()->saveAndReturn($user->current_team_id, 'uk', true, $contactsCount);
        $campaign = SmsCampaign::factory()->state(['team_id' => $user->currentTeam->id])->create();

        $texts = SmsCampaignText::factory()->count(5)->create([
            'sms_campaign_id' => $campaign->id,
        ]);

        $senderids = SmsCampaignSenderId::factory()->count(5)->create([
            'sms_campaign_id' => $campaign->id,
        ]);

        $offers = Offer::factory()->count(5)->create([
            'team_id' => $user->currentTeam->id,
        ])->each(function ($model) use ($campaign) {
            $campaign->offers()->attach($model->id);
        });

        $campaign->setSettings(SmsCampaignSettingsData::from([
            'sms_routing_plan_id' => $plan->id,
            'send_time' => null,
            'send_amount' => 100,
        ]));
        $campaign->save();

        return ['user' => $user, 'campaign' => $campaign, 'contacts' => $contacts, 'texts' => $texts,
            'senderids' => $senderids, 'offers' => $offers, 'route1' => $route1];
    }
}
