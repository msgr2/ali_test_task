<?php

namespace App\Services;

use App\Models\Segment;
use App\Models\SmsCampaignSend;
use ClickHouseDB\Client;
use DB;
use Log;

class SmsCampaignContactService
{

    public static function getContacts(SmsCampaignSend $campaignSend): array
    {
        Log::debug('getContacts', ['campaignSend_id' => $campaignSend->id]);

        if ($campaignSend->getSettings()->multistep_settings) {
            return SmsCampaignContactMultistepService::getContactsForMultistepCampaign($campaignSend);
        }

        return self::getContactsForSimpleCampaign($campaignSend);
    }

    private static function getContactsForSimpleCampaign(SmsCampaignSend $campaignSend)
    {
        $settings = $campaignSend->getSettings();

        return self::queryContacts($campaignSend, self::getBaseQuery($campaignSend), $settings->send_amount);
    }

    protected static function queryContacts(SmsCampaignSend $campaignSend, string $query, int $limit): array
    {
        if ($limit == 0) {
            $limit = 9999999999;
        }
        /** @var Client $client */
        $client = DB::connection('clickhouse')->getClient();
        $res = $client->select($query,
            ['limit' => $limit, 'team_id' => $campaignSend->campaign->team_id]);
        Log::debug("queried contacts: " . $res->count(), ['contacts' => array_merge($res->statistics(),
            $res->responseInfo()
        )]);

        return $res->rows();
    }

    protected static function getBaseQuery(SmsCampaignSend $campaignSend)
    {
        $settings = $campaignSend->getSettings();
        $segmentQueryStr = '1=1';
        $segmentQueries = collect([]);
        if (count($settings->segment_ids) > 0) {
            Log::debug('segment_ids', ['segment_ids' => $settings->segment_ids]);
            foreach ($settings->segment_ids as $segmentId) {
                /** @var Segment $segment */
                $segment = Segment::where('id', $segmentId)->first();
                $segmentQueries[] = $segment->getWhereFromSegment()->toSql();
                Log::debug("segmet query", ['query' => $segment->getWhereFromSegment()->toSql()]);
                Log::debug("segment meta", ['meta' => $segment->meta]);
            }

            $segmentQueryStr = $segmentQueries->implode(' OR ');
        }

        return "
        SELECT 
            team_id,
phone_normalized,
 contact_id,
 foreign_id,
last_sent,
last_clicked,
 sent_count,
 clicked_count,
 leads_count,
 sales_count,
 profit_sum,
 network_brand,
 network_id,
network_reason,
 phone_is_good,
 phone_is_good_reason,
 name,
 country_id,
 state_id,
state_id_reason,
 custom1_str,
 custom2_str,
 custom3_str,
 custom4_str,
 custom5_str,
 custom1_int,
 custom2_int,
 custom3_int,
 custom4_int,
 custom5_int,
 custom1_dec,
 custom2_dec,
 custom1_datetime,
 custom2_datetime,
 custom3_datetime,
 custom4_datetime,
 custom5_datetime,
 meta,
 date_created,
 date_updated,
 is_deleted
        from contacts_sms_view
        where team_id = '{$campaignSend->campaign->team_id}'
          AND ({$segmentQueryStr})
        and is_deleted = 0";
    }
}