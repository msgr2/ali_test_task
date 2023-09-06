<?php

namespace App\Jobs;

use App\Data\BuildSmsToSendSmsData;
use App\Data\CampaignSendToBuildSmsData;
use App\Data\SmsRoutingPlanSelectedData;
use App\Data\SmsRoutingPlanSelectorData;
use App\Models\Country;
use App\Models\OfferCampaign;
use App\Models\SmsCampaignLog;
use App\Models\SmsCampaignSend;
use App\Models\SmsCampaignSenderid;
use App\Models\SmsRoutingPlan;
use App\Services\BalanceService;
use App\Services\SendingProcess\Data\BuildSmsData;
use App\Services\SendingProcess\Routing\SmsRoutingPlanSelectorService;
use App\Services\SendingProcess\TextService;
use App\Services\SmsRoutingPlanService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Log;
use Str;

class buildSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public function __construct(public CampaignSendToBuildSmsData $dto)
    {
    }

    public function handle(): void
    {
        Log::debug('starting build sms job', ['sms_campaign_send_id' => $this->dto->sms_campaign_send_id]);
        //check stop conditions
        if (SmsCampaignSend::find($this->dto->sms_campaign_send_id)->status == 'stopped') {
            Log::debug('campaign stopped, returning');
            return;
        }

        $res = Cache::pull($this->dto->phone_normalized . '_' . $this->dto->team_id);
        if ($res) {
            Log::warning('Duplicate sms cache: ' . $this->dto->phone_normalized . '_' . $this->dto->team_id);
            return;
        }

        Cache::put($this->dto->phone_normalized . '_' . $this->dto->team_id, true, 20);

        $data = new BuildSmsData();
        $data->sendToBuildSmsData = $this->dto;
        $data->sms_id = Str::uuid()->toString();
        //decide on route.
        if (!$this->setRoute($data)) {
            return;
        }
        Log::debug('finished setting route, processing text', ['sms_id' => $data->sms_id]);

        try {
            TextService::processMsg($data);
        } catch (Exception $e) {
            Log::error('Error processing text', ['sms_id' => $data->sms_id, 'error' => $e->getMessage()]);
            $this->fail($e);
            return;
        }

        $this->setSenderids($data);

        //deduct balance
        if (!$this->deductBalance($data)) {
            return;
        }

        //submit to sms build queue
        $data = BuildSmsToSendSmsData::from([
            'buildSmsData' => $data,
        ]);
        SendSmsJob::dispatch($data);
    }

    private function setRoute(BuildSmsData $data): bool
    {
        if ($this->dto->sms_routing_plan_id) {
            $plan = SmsRoutingPlan::where(['id' => $this->dto->sms_routing_plan_id, 'team_id' => $this->dto->team_id])
                ->first();
        } else {
            $plan = SmsRoutingPlanService::getDefaultRoutingPlan($this->dto->team_id);
        }
        if (!$plan) {
            throw new Exception('No routing plan found for team: ' . $this->dto->team_id);
        }
        $data->sms_routing_plan_id = $plan->id;
        $selected = SmsRoutingPlanSelectorService::createSelectorForBuildSms($plan, $data);
        if ($selected instanceof SmsRoutingPlanSelectorData) {
            SmsCampaignLog::create([
                'caller_id' => $data->sendToBuildSmsData->sms_campaign_send_id,
                'caller_type' => SmsCampaignSend::class,
                'text' => 'failed to find route',
                'meta' => json_encode($selected->toArray()),
            ]);

            $this->fail('failed to find route');
            Log::info('failed to find route', ['sms_id' => $data->sms_id]);
            return false;
        }
        /** @var SmsRoutingPlanSelectedData $selected */
        $data->selectedRoute = $selected;
        return true;
    }

    private function setSenderids(BuildSmsData $data)
    {
        $country = Country::where(['id' => $this->dto->country_id])->firstOrFail();
        if (!$country->sender_id) {
            Log::debug('country has no sender id: ' . $country->id, ['sms_id' => $data->sms_id]);
            return true;
        }

        $senderids = SmsCampaignSenderid::where(['sms_campaign_id' => $this->dto->sms_campaign_id])
            ->where(['is_active' => true])
            ->get();
        if ($senderids->count() == 0) {
            Log::debug('no senderids found for campaign: ' . $this->dto->sms_campaign_id, ['sms_id' => $data->sms_id]);
            return false;
        }

        $senderid = $senderids[$data->sendToBuildSmsData->counter % $senderids->count()];
        $data->selected_senderderid_id = $senderid->id;
        $data->selected_senderderid_text = $senderid->text;

        return true;
    }

    private function deductBalance(BuildSmsData $data): bool
    {
        Log::debug('deducting balance');
        $balance = BalanceService::getTeamBalance($this->dto->team_id);
        $toDeduct = ($data->selectedRoute->route_rate * $data->final_text_msg_parts);
        if ($balance - $toDeduct < 0) {
            $this->fail('not enough balance');
            Log::info('fail - not enough balance', ['sms_id' => $data->sms_id]);
            return false;
        }
        BalanceService::deductBalance($this->dto->team_id, $toDeduct, [
            'type' => 'campaign_send',
            'campaign_send_id' => $this->dto->sms_campaign_send_id,
            'sms_id' => $data->sms_id,
            'sms_routing_plan_id' => $data->sendToBuildSmsData->sms_routing_plan_id,
        ]);
        Log::debug('deducting balance', ['sms_id' => $data->sms_id, 'balance' => $balance, 'deducted' => $toDeduct]);

        return true;
    }
}
