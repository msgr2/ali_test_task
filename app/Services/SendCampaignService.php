<?php

namespace App\Services;

use App\Enums\SmsCampaignStatusEnum;
use App\Exceptions\CampaignSendException;
use App\Jobs\SendCampaignJob;
use App\Models\SmsCampaign;
use App\Models\SmsCampaignSend;
use App\Models\SmsRoutingPlan;
use Illuminate\Support\Facades\Log;

class SendCampaignService
{
    public static function send(SmsCampaign $campaign)
    {
        $campaign->getSettings();
        self::isReadyToSend($campaign);
        $campaignSend = $campaign->sends()->create([
            'status' => SmsCampaignStatusEnum::in_progress(),
            'meta' => $campaign->meta,
        ]);

        Log::debug('Sending campaign_send: ' . $campaignSend->id);
        SendCampaignJob::dispatch($campaignSend);
    }

    private static function isReadyToSend(SmsCampaign $campaign)
    {
        $settings = $campaign->getSettings();
        if (!isset($settings->sms_routing_plan_id)) {
            $plan = SmsRoutingPlan::where(['is_team_default' => true])->first();
            if (!$plan) {
                $plan = SmsRoutingPlan::create([
                    'name' => 'Default',
                    'is_team_default' => true,
                    'team_id' => $campaign->team_id,
                ]);
            }

            $settings->sms_routing_plan_id = $plan->id;
            $campaign->setSettings($settings);
            $campaign->save();
        }

        return true;
    }

    public static function continueSend(SmsCampaignSend $campaignSend)
    {
        Log::debug("Continue sending campaign: {$campaignSend->id}");

        SendCampaignJob::dispatch($campaignSend);
    }
}
