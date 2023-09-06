<?php

namespace Tests\Feature\Api;

use App\Enums\SmsCampaignStatusEnum;
use App\Models\Clickhouse\Contact;
use App\Models\Offer;
use App\Models\SmsCampaign;
use App\Models\SmsRoute;
use App\Models\SmsRoutingPlan;
use App\Models\SmsSendlog;
use App\Services\BalanceService;

class SendSmsCampaignTest extends BaseApiTest
{
    public function testListCampaigns()
    {
        $this->getJson('/api/v1/sms/campaigns')->assertOk();
    }

    public function testCreateCampaign()
    {
        $this->postJson('/api/v1/sms/campaigns', [
            'name' => 'Test campaign'
        ])->assertCreated();
    }

    public function testCampaignVars()
    {
        $plan = SmsRoutingPlan::factory()->create([
            'team_id' => $this->user->currentTeam->id,
            'name' => 'Test plan',
        ]);
        $contacts = Contact::factory()->saveAndReturn($this->user->current_team_id);
        $campaign = SmsCampaign::factory()->state(['team_id' => $this->user->currentTeam->id])->create();
        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/texts", [
            'text' => 'Test campaign var',
        ])->assertCreated();

        $this->getJson("/api/v1/sms/campaigns/{$campaign->id}/texts")->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'text',
                    'is_active',
                    'created_at',
                    'updated_at',
                ]
            ]
        ])->assertJsonCount(1, 'data.*')->assertOk();

        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/senderids", [
            'text' => 'abcydu',
        ])->assertCreated();
        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/senderids", [
            'text' => 'abcyasfwrduaisudhiuh',
        ])->assertUnprocessable();

        $this->getJson("/api/v1/sms/campaigns/{$campaign->id}/senderids")->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'text',
                    'is_active',
                    'created_at',
                    'updated_at',
                ]
            ]
        ])->assertJsonCount(1, 'data.*')->assertOk();

        $offer = Offer::factory()->state([
            'team_id' => $this->user->currentTeam->id,
        ])->create();

        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/offers", [
            'offer_id' => $offer->id,
        ])->assertCreated();

        $this->getJson("/api/v1/sms/campaigns/{$campaign->id}/offers")
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'offer_id',
                        'is_active',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ])->assertJsonCount(1, 'data.*')->assertOk();

        $this->putJson("/api/v1/sms/campaigns/{$campaign->id}", [
            'name' => 'Test campaign 2',
        ])->assertOk();

        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/send-manual", [
            'sms_routing_plan_id' => $plan->id,
            'send_amount' => 100,
        ])->assertOk();
    }

    public function testSendManualCampaignApi()
    {
        $plan = SmsRoutingPlan::factory()->create([
            'team_id' => $this->user->currentTeam->id,
            'name' => 'Test plan',
        ]);
        BalanceService::addBalance($this->user->current_team_id, 1000, []);
        $route1 = SmsRoute::factory()->withRouteRates()->withSmppConnection()->create([
            'team_id' => $this->user->currentTeam->id,
        ]);
        $route2 = SmsRoute::factory()->withRouteRates()->withSmppConnection()->create([
            'team_id' => $this->user->currentTeam->id,
        ]);
        $contacts = Contact::factory()->saveAndReturn($this->user->current_team_id);
        $campaign = SmsCampaign::factory()->state(['team_id' => $this->user->currentTeam->id])->create();
        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/texts", [
            'text' => 'Test campaign var',
        ])->assertCreated();

        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/senderids", [
            'text' => 'abcydu',
        ])->assertCreated();
        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/senderids", [
            'text' => 'abcyasfwrduaisudhiuh',
        ])->assertUnprocessable();

        $offer = Offer::factory()->state([
            'team_id' => $this->user->currentTeam->id,
        ])->create();

        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/offers", [
            'offer_id' => $offer->id,
        ])->assertCreated();

        $this->putJson("/api/v1/sms/campaigns/{$campaign->id}", [
            'name' => 'Test campaign 2',
            'settings.send_time' => '00:00',
            'settings.send_date' => '2021-01-01',
        ])->assertOk();

        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/send-manual", [
            'sms_routing_plan_id' => $plan->id,
            'send_amount' => 100,
        ])->assertOk();
    }

    public function testSendManualCampaignWithBadRouteApi()
    {
        $plan = SmsRoutingPlan::factory()->create([
            'team_id' => $this->user->currentTeam->id,
            'name' => 'Test plan',
        ]);
        BalanceService::addBalance($this->user->current_team_id, 1000, []);
        $route1 = SmsRoute::factory()->withRouteRates()->withSmppConnection()->create([
            'team_id' => $this->user->currentTeam->id,
        ]);
        $route1->smppConnection->update([
            'url' => 'asopifj',
        ]);
        $contacts = Contact::factory()->saveAndReturn($this->user->current_team_id);
        $campaign = SmsCampaign::factory()->state(['team_id' => $this->user->currentTeam->id])->create();
        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/texts", [
            'text' => 'Test campaign var',
        ])->assertCreated();

        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/senderids", [
            'text' => 'abcydu',
        ])->assertCreated();
        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/senderids", [
            'text' => 'abcyasfwrduaisudhiuh',
        ])->assertUnprocessable();

        $offer = Offer::factory()->state([
            'team_id' => $this->user->currentTeam->id,
        ])->create();

        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/offers", [
            'offer_id' => $offer->id,
        ])->assertCreated();

        $this->putJson("/api/v1/sms/campaigns/{$campaign->id}", [
            'name' => 'Test campaign 2',
            'settings.send_time' => '00:00',
            'settings.send_date' => '2021-01-01',
        ])->assertOk();

        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/send-manual", [
            'sms_routing_plan_id' => $plan->id,
            'send_amount' => '100',
        ])->assertOk();
        //logs - testing.ERROR: Error creating smpp client {"sms_id":"32de9f10-5004-41c4-84ec-d049226f982c","error":"No valid hosts was found"}
    }

    public function testSendManualCampaignAfterCampaignAssertions()
    {
        $plan = SmsRoutingPlan::factory()->create([
            'team_id' => $this->user->currentTeam->id,
            'name' => 'Test plan',
        ]);
        BalanceService::addBalance($this->user->current_team_id, 1000, []);
        $route1 = SmsRoute::factory()->withRouteRates()->withSmppConnection()->create([
            'team_id' => $this->user->currentTeam->id,
        ]);
        $contacts = Contact::factory()->saveAndReturn($this->user->current_team_id);
        $campaign = SmsCampaign::factory()->state(['team_id' => $this->user->currentTeam->id])->create();
        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/texts", [
            'text' => 'Test campaign var',
        ])->assertCreated();

        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/senderids", [
            'text' => 'abcydu',
        ])->assertCreated();
        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/senderids", [
            'text' => 'abcyasfwrduaisudhiuh',
        ])->assertUnprocessable();

        $offer = Offer::factory()->state([
            'team_id' => $this->user->currentTeam->id,
        ])->create();

        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/offers", [
            'offer_id' => $offer->id,
        ])->assertCreated();

        $this->putJson("/api/v1/sms/campaigns/{$campaign->id}", [
            'name' => 'Test campaign 2',
            'settings.send_time' => '00:00',
            'settings.send_date' => '2021-01-01',
        ])->assertOk();

        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/send-manual", [
            'sms_routing_plan_id' => $plan->id,
            'send_amount' => 100,
        ])->assertOk();

        $this->assertEquals(1000 - count($contacts) *
            $route1->getRateForCountry($contacts[0]->country_id),
            BalanceService::getTeamBalance
            ($this->user->current_team_id)
        );
        $logs = SmsSendlog::where('sms_campaign_id', '=', $campaign->id)->get();
        $this->assertSameSize($contacts, $logs);

//        $this->assertEquals($campaign->fresh()->status, SmsCampaignStatusEnum::sent()->value);
//        $this->assertEquals($campaign->fresh()->sent_at->format('Y-m-d H:i'), '2021-01-01 00:00');
//        $this->assertEquals($campaign->fresh()->sent_amount, 100);
//        $this->assertEquals($campaign->fresh()->sent_count, count($contacts));

    }
}
