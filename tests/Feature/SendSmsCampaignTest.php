<?php

namespace Tests\Feature;

use App\Models\Clickhouse\Contact;
use App\Models\Lists;
use App\Models\Offer;
use App\Models\SmsCampaign;
use App\Models\SmsCampaignSenderid;
use App\Models\SmsCampaignText;
use App\Models\SmsSendlog;
use App\Models\User;
use App\Services\CountryService;
use App\Services\SendCampaignService;
use Database\Factories\SegmentFactory;
use Database\Factories\SendSmsCampaignFactory;
use Log;
use Tests\TestCase;

class SendSmsCampaignTest extends TestCase
{
    public $user;

    public function test_send_campaign_simple()
    {
        $res = SendSmsCampaignFactory::new()->withBasicSetup();
        SendCampaignService::send($res['campaign']);
        $this->assertDatabaseHas('sms_sendlogs', [
            'sms_campaign_id' => $res['campaign']->id,
        ], 'clickhouse');
    }

    public function test_send_campaign_with_split_rule()
    {
        $res = SendSmsCampaignFactory::new()->withBasicSetup();
        SendCampaignService::send($res['campaign']);
        $this->assertDatabaseHas('sms_sendlogs', [
            'sms_campaign_id' => $res['campaign']->id,
        ], 'clickhouse');
    }

    public function test_send_campaign_with_shortener()
    {
        $res = SendSmsCampaignFactory::new()->withBasicSetup();
        $res['texts']->each(function ($model) use ($res) {
            $model->update([
                'text' => 'Test text {domain}',
            ]);
        });
        SendCampaignService::send($res['campaign']);
        $this->assertDatabaseHas('sms_sendlogs', [
            'sms_campaign_id' => $res['campaign']->id,
        ], 'clickhouse');
    }

    public function test_send_campaign_with_brand_segments()
    {
        $res = SendSmsCampaignFactory::new()->withBasicSetup();
        $contacts = $res['contacts'];
        $contacts = collect($contacts->toArray());

        $contactsPerBrand = $contacts->groupBy('network_brand')->map(function ($item) {
            return $item->count();
        });
//        dd($contactsPerBrand); array:18 [
//    "Optus" => 52
//    "GEMCO" => 9
//    "Telstra" => 95
//    "Pactel" => 22
//    "Lycamobile" => 22
//    "CommTel NS" => 19
//    "AAPT" => 28
//    "Vodafone" => 35
//    "RailCorp" => 18
//    3 => 35
//    "Ozitel" => 18
//    "One.Tel" => 44
//    "Norfolk Is." => 18
//    "3GIS" => 16
//    "NBN" => 26
//    "Truphone" => 17
//    "VicTrack" => 13
//    "Airnet" => 13
//  ]
        //get random 5 brands with the values preserve keys
        $brands = $contactsPerBrand->random(2, true);

        Log::debug('brands', $brands->toArray());
        //get sum of values
        $sum = $brands->sum();
        $brandNames = $brands->keys();
        $segment = SegmentFactory::new()->withFilterNetwork($brandNames->toArray())->create();

        $res['campaign']->update([
            'segment_id' => $segment->id,
        ]);
        /** @var SmsCampaign $campaign */
        $campaign = $res['campaign'];
        $settings = $campaign->getSettings();
        $settings->segment_ids[] = $segment->id;
        $campaign->setSettings($settings);

        SendCampaignService::send($res['campaign']);
        $this->assertDatabaseHas('sms_sendlogs', [
            'sms_campaign_id' => $res['campaign']->id,
        ], 'clickhouse');
        $sent = SmsSendlog::where('sms_campaign_id', $res['campaign']->id)->get();

        $this->assertEquals($sum, $sent->count());
    }

    public function test_send_campaign_segment_with_sent_0()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
        $res = SendSmsCampaignFactory::new()->withBasicSetup();
        $contacts = $res['contacts'];
        $contacts = collect($contacts->toArray());

        $segment = SegmentFactory::new()->withNumbersSample1()->create();

        $res['campaign']->update([
            'segment_id' => $segment->id,
        ]);
        /** @var SmsCampaign $campaign */
        $campaign = $res['campaign'];
        $settings = $campaign->getSettings();
        $settings->segment_ids[] = $segment->id;
        $campaign->setSettings($settings);

        SendCampaignService::send($res['campaign']);
        $this->assertDatabaseHas('sms_sendlogs', [
            'sms_campaign_id' => $res['campaign']->id,
        ], 'clickhouse');
        $sent = SmsSendlog::where('sms_campaign_id', $res['campaign']->id)->get();

        $this->assertEquals($contacts->count(), $sent->count());
    }

    public function test_send_campaign_segment_with_multiple_geos_segments()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
        $res = SendSmsCampaignFactory::new()->withBasicSetup();
        $contacts = $res['contacts'];
        $contacts = collect($contacts->toArray());
        $segment = SegmentFactory::new()->withNumbersSample1()->create();

        $contacts2 = Contact::factory()->saveAndReturn($res['user']->current_team_id, 'au', true);
        $contacts2 = collect($contacts2->toArray());
        $segment2 = SegmentFactory::new()->withNumbersSample1('au')->create();

        $res['campaign']->update([
            'segment_id' => $segment->id,
        ]);
        /** @var SmsCampaign $campaign */
        $campaign = $res['campaign'];
        $settings = $campaign->getSettings();
        $settings->segment_ids[] = $segment->id;
        $settings->segment_ids[] = $segment2->id;
        $campaign->setSettings($settings);

        SendCampaignService::send($res['campaign']);
        $this->assertDatabaseHas('sms_sendlogs', [
            'sms_campaign_id' => $res['campaign']->id,
        ], 'clickhouse');
        $sent = SmsSendlog::where('sms_campaign_id', $res['campaign']->id)->get();

        $this->assertEquals($contacts->count() + $contacts2->count(), $sent->count());
    }
}
