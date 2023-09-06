<?php

namespace App\Jobs;

use App\Data\BuildSmsToSendSmsData;
use App\Models\SmsRoute;
use App\Models\SmsRouteSmppConnection;
use App\Models\SmsSendlog;
use App\Services\PricingService;
use App\Services\SendingProcess\Routing\SmsRoutingSmppClientService;
use DB;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public BuildSmsToSendSmsData $dto)
    {
    }

    public function handle(): void
    {
        /**
         *             'sms_id' => $data->sms_id,
         * 'phone_normalized' => $data->sendToBuildSmsData->phone_normalized,
         * 'contact_id' => $data->sendToBuildSmsData->contact_id,
         * 'final_text' => $data->finalText,
         * 'final_text_msg_parts' => $data->final_text_msg_parts,
         * 'selected_route_id' => $data->selectedRoute->selected_route_id,
         * 'sms_campaign_id' => $data->sendToBuildSmsData->sms_campaign_id,
         * 'sms_campaign_send_id' => $data->sendToBuildSmsData->sms_campaign_id,
         */
        Log::debug("Submitting sms to sms provider", ['sms_id' => $this->dto->buildSmsData->sms_id]);

        //submit to sms provider
        $route = SmsRoute::find($this->dto->buildSmsData->selectedRoute->selected_route_id);
        self::testDbConnection();
        if ($route->connection_type === SmsRouteSmppConnection::class) {
            $smpp = SmsRouteSmppConnection::find($route->connection_id);
            try {
                $client = SmsRoutingSmppClientService::createSmppClient($smpp);
            } catch (Exception $e) {
                Log::error("Error creating smpp client",
                    ['sms_id' => $this->dto->buildSmsData->sms_id, 'error' => $e->getMessage()]);
                $this->fail();
                return;
            }

            $res = $client->sendSms($this->dto->buildSmsData->selected_senderderid_text,
                $this->dto->buildSmsData->sendToBuildSmsData->phone_normalized,
                $this->dto->buildSmsData->finalText,
                $this->dto->buildSmsData->final_text_is_unicode,
                $this->dto->buildSmsData->final_text_msg_parts > 1,
            );
            if (!$res) {
                $this->fail('Failed to send SMS');
                return;
            }
            $msgId = isset($results['msgid']) ? $results['msgid'] : '';

            $client->syncSmppDlrs();

        } else {
            Log::warning('Other connection types not implemented yet');
        }
        //save results to send log
        $this->saveResultsToSmsSendlog($res);
    }

    private static function testDbConnection()
    {
        try {
            DB::connection('pgsql')->select('select 1=1;');
        } catch (Exception $e) {
            DB::connection('pgsql')->reconnect();
        }
        try {
            DB::connection('clickhouse')->select('select 1=1;');
        } catch (Exception $e) {
            DB::connection('clickhouse')->reconnect();
        }
    }

    private function saveResultsToSmsSendlog($foreignId)
    {
        //        `dlr_code` Nullable(UInt8),
        //        `dlr_str` Nullable(String),
        //        `click_meta` Nullable(String),
        //        `time_clicked` Nullable(DateTime),
        //        `meta` Nullable(String)

        $smsSendLog = new SmsSendLog();
        $smsSendLog->foreign_id = $foreignId;
        $smsSendLog->sms_id = $this->dto->buildSmsData->sms_id;
        $smsSendLog->segment_id = $this->dto->buildSmsData->sendToBuildSmsData->segment_id;
        $smsSendLog->network_id = $this->dto->buildSmsData->sendToBuildSmsData->network_id;
        $smsSendLog->offer_id = isset($this->dto->buildSmsData->selectedOffer) ?
            $this->dto->buildSmsData->selectedOffer->id : null;
        $smsSendLog->sender_id = $this->dto->buildSmsData->selected_senderderid_text;
        $smsSendLog->sender_id_id = $this->dto->buildSmsData->selected_senderderid_id;
        $smsSendLog->cost_platform_profit = PricingService::getCostOfCustomRoute
        ($this->dto->buildSmsData->sendToBuildSmsData->team_id);
        $smsSendLog->cost_user_vendor_cost = $this->dto->buildSmsData->selectedRoute->route_rate;
//        $smsSendLog->original_url = $this->dto->buildSmsData->selectedOffer?->original_url;
        $smsSendLog->sms_routing_plan_id = $this->dto->buildSmsData->sendToBuildSmsData->sms_routing_plan_id;
        $smsSendLog->sms_routing_plan_rule_id = $this->dto->buildSmsData->selectedRoute->selected_rule_id;
        $smsSendLog->sms_rule_selected_data = json_encode($this->dto->buildSmsData->selectedRoute);
        $smsSendLog->shortened_url = $this->dto->buildSmsData->sms_shortlink;
        $smsSendLog->domain_id = isset($this->dto->buildSmsData->domain) ? $this->dto->buildSmsData->domain->id : null;
        $smsSendLog->campaign_text_id = isset($this->dto->buildSmsData->selectedCampaignText) ?
            $this->dto->buildSmsData->selectedCampaignText->id : null;
        $smsSendLog->country_id = $this->dto->buildSmsData->sendToBuildSmsData->country_id;
        $smsSendLog->sent_at = now();
        $smsSendLog->is_sent = 1;
        $smsSendLog->contact_id = $this->dto->buildSmsData->sendToBuildSmsData->contact_id;
        $smsSendLog->phone_normalized = $this->dto->buildSmsData->sendToBuildSmsData->phone_normalized;
        $smsSendLog->sms_campaign_id = $this->dto->buildSmsData->sendToBuildSmsData->sms_campaign_id;
        $smsSendLog->sms_campaign_send_id = $this->dto->buildSmsData->sendToBuildSmsData->sms_campaign_send_id;
        $smsSendLog->final_text = $this->dto->buildSmsData->finalText;
        $smsSendLog->text_parts = $this->dto->buildSmsData->final_text_msg_parts;
        $smsSendLog->sms_routing_route_id = $this->dto->buildSmsData->selectedRoute->selected_route_id;
        $smsSendLog->team_id = $this->dto->buildSmsData->sendToBuildSmsData->team_id;
        $smsSendLog->updated_datetime = now();
        $smsSendLog->save();
    }
}
