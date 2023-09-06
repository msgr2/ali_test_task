<?php

namespace App\Services;

use App\Data\CampaignMultistepStatusData;
use App\Enums\SmsCampaignStatusEnum;
use App\Models\Domain;
use App\Models\Segment;
use App\Models\SmsCampaignSend;
use Carbon\Carbon;
use ClickHouseDB\Client;
use ClickHouseDB\Query\Query;
use Database\Factories\DomainFactory;
use DB;
use Log;
use Tinderbox\ClickhouseBuilder\Query\TwoElementsLogicExpression;

class SmsCampaignContactMultistepService extends SmsCampaignContactService
{
    protected static function getContactsForMultistepCampaign(SmsCampaignSend $campaignSend): array
    {
        $settings = $campaignSend->getMultistepSettings();
        Log::debug("multistep settings", ['settings' => $settings]);

        $status = $campaignSend->getMultistepStatus();
        Log::debug('getContactsForMultistepCampaign', ['status' => $status]);


        if ($status->current_step === 0) {
            self::handleFirstStep($campaignSend);
        }

        return self::handleStep($campaignSend);
    }

    private static function handleFirstStep(SmsCampaignSend $campaignSend): bool
    {
        $settings = $campaignSend->getMultistepSettings();
        $status = $campaignSend->getMultistepStatus();
        $baseQuery = self::getBaseQuery($campaignSend);

        $total_available_contacts = self::getTotalAvailableContacts($campaignSend);
        $status->total_available_contacts = $total_available_contacts;
        $status->start_timestamp = microtime(true);


        //todo check also in manual send
//        if ($total_available_contacts == 0) {
//            Log::debug("No contacts found for campaign {$campaignSend->id}");
//            todo: add user notification
//            $status->status = SmsCampaignStatusEnum::sent()->value;
//            $campaignSend->setMultistepStatus($status);
//            return false;
//        }

        Log::debug("total_available_contacts: {$total_available_contacts}");

        $brands = self::getBrandsFromBaseQuery($baseQuery);
        $status->initial_brands = $brands;
        Log::debug("segment network brands", ['brands' => $brands]);
        $campaignSend->setMultistepStatus($status);

        return true;
    }

    private static function getTotalAvailableContacts(SmsCampaignSend $campaignSend): int
    {
        $baseQuery = self::getBaseQuery($campaignSend);
        $query = "select count(*) as count from ({$baseQuery})";
        $total_available_contacts = ClickhouseService::query($query);
        return $total_available_contacts[0]['count'];
    }

    private static function getBrandsFromBaseQuery(string $baseQuery): array
    {
        $query =
            "select count(*) as count, network_brand from ({$baseQuery}) group by network_brand order by count asc";
        $brands = ClickhouseService::query($query, 'network_brand');
        return collect($brands)->map(function ($item) {
            return $item['count'];
        })->toArray();
    }

    private static function handleStep(SmsCampaignSend $campaignSend): array
    {
        $settings = $campaignSend->getMultistepSettings();
        $status = $campaignSend->getMultistepStatus();
        $limit = $settings->step_size;


        //todo: only if step > 0 check stats and take actions like stopping, disabling routes etc'
        $status = self::createLastStepStats($campaignSend, $status);

        $baseQuery = self::getBaseQuery($campaignSend);
        $campaignSend->next_step_timestamp =
            now()->addMinutes($campaignSend->getMultistepSettings()->step_delay)->toDateTime();
        $status->current_step++;
        $status->last_sent_timestamp = microtime(true);

        $firstSent = Carbon::createFromTimestamp($status->start_timestamp)->toDateTimeString();
        $query = "select * from ({$baseQuery}) where (last_sent < '{$firstSent}' OR last_sent IS NULL) limit {$limit}";


        Log::debug('CH query', ['query' => $query]);

        //----

        $campaignSend->setMultistepStatus($status);

        $baseQuery = self::getBaseQuery($campaignSend);
        $query = "select * from ({$baseQuery}) limit {$limit}";
        Log::debug('CH query', ['query' => $query]);

        //check if we have available routes after disabling campaign
        $status->current_step++;
        $status->total_sent += $limit;
        $status->last_sent_timestamp = microtime(true);
        $campaignSend->next_step_timestamp =
            now()->addMinutes($campaignSend->getMultistepSettings()->step_delay)->toDateTime();

        $campaignSend->setMultistepStatus($status);
        $contacts = self::queryContacts($campaignSend, $query, $limit);
        if (count($contacts) < $limit) {
            Log::debug("No more contacts found for campaign {$campaignSend->id}");
            $campaignSend->status = SmsCampaignStatusEnum::sent()->value;
            $status->status = SmsCampaignStatusEnum::sent()->value;
        }
        $campaignSend->setMultistepStatus($status);
        $campaignSend->save();

        return $contacts;
    }

    private static function createLastStepStats(SmsCampaignSend $campaignSend, CampaignMultistepStatusData $status)
    {
        $previousStepTimestamp = Carbon::createFromTimestamp($campaignSend->getMultistepStatus()->last_sent_timestamp)
            ->toDateTimeString();
        Log::debug("previous step timestamp", ['timestamp' => $previousStepTimestamp]);

        //get stats of previous step
        $baseLastStepStats =
            "select * from sms_sendlogs_v where sms_campaign_send_id = '{$campaignSend->id}' and sent_at >= '{$previousStepTimestamp}'";
        Log::debug("previous step stats query", ['query' => $baseLastStepStats]);

        $domainStats = <<<SQL
SELECT domain_id, 
       countIf(is_clicked = 1) AS total_clicks, 
       count() AS total_sent, 
       (countIf(is_clicked = 1) * 1.0 / count()) * 100 AS ctr
FROM ($baseLastStepStats)
WHERE is_sent = 1
GROUP BY domain_id;
SQL;
        $domainStats = ClickhouseService::query($domainStats, 'domain_id');
        //replace domain_id with domain name from Domain table
        $domainStats = collect($domainStats)->map(function ($item, $key) {
//            $domain = Domain::find($item['domain_id']);
            //todo: fix after domain api implementation
            $domain = Domain::factory()->create();
            $item['domain'] = $domain->domain;
            unset($item['domain_id']);
            return $item;
        })->toArray();

        Log::debug("domain stats", ['domainStats' => $domainStats]);

        $senderid_performance = <<<SQL
SELECT sender_id, 
       countIf(is_clicked = 1) AS total_clicks, 
       count() AS total_sent, 
       (countIf(is_clicked = 1) * 1.0 / count()) * 100 AS ctr
FROM ($baseLastStepStats)
WHERE is_sent = 1
GROUP BY sender_id;
SQL;
        $senderid_performance = ClickhouseService::query($senderid_performance, 'sender_id');
        Log::debug("senderid_performance", ['senderid_performance' => $senderid_performance]);

        $route_performance = <<<SQL
SELECT sms_routing_route_id,
         countIf(is_clicked = 1) AS total_clicks,
         count() AS total_sent,
         (countIf(is_clicked = 1) * 1.0 / count()) * 100 AS ctr
FROM ($baseLastStepStats)
WHERE is_sent = 1
GROUP BY sms_routing_route_id;
SQL;

        $route_performance = ClickhouseService::query($route_performance, 'sms_routing_route_id');
        Log::debug("route_performance", ['route_performance' => $route_performance]);

        $campaignTextsPerformance = <<<SQL
SELECT campaign_text_id,
         countIf(is_clicked = 1) AS total_clicks,
         count() AS total_sent,
         (countIf(is_clicked = 1) * 1.0 / count()) * 100 AS ctr
FROM ($baseLastStepStats)
WHERE is_sent = 1
GROUP BY campaign_text_id;
SQL;
        $campaignTextsPerformance = ClickhouseService::query($campaignTextsPerformance, 'campaign_text_id');
        Log::debug("campaignTextsPerformance", ['campaignTextsPerformance' => $campaignTextsPerformance]);

        $offersPerformance = <<<SQL
SELECT offer_id,
         countIf(is_clicked = 1) AS total_clicks,
         count() AS total_sent,
         (countIf(is_clicked = 1) * 1.0 / count()) * 100 AS ctr
FROM ($baseLastStepStats)
WHERE is_sent = 1
GROUP BY offer_id;
SQL;
        $offersPerformance = ClickhouseService::query($offersPerformance, 'offer_id');
        Log::debug("offersPerformance", ['offersPerformance' => $offersPerformance]);

        $status->steps_performance[$status->current_step - 1] = [
            'domain' => $domainStats,
            'senderid' => $senderid_performance,
            'route' => $route_performance,
            'campaign_text' => $campaignTextsPerformance,
            'offers' => $offersPerformance,
        ];

        return $status;
//            $networkBrandStats=<<<SQL
//SELECT network_brand,
//         countIf(is_clicked = 1) AS total_clicks,
//         count() AS total_sent,
//         (countIf(is_clicked = 1) * 1.0 / count()) * 100 AS ctr
//FROM ($baseLastStepStats)
//WHERE is_sent = 1
//GROUP BY network_brand;
//SQL;


    }
}