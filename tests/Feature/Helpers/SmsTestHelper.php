<?php

namespace Tests\Feature\Helpers;

use App\Services\ClickhouseService;

class SmsTestHelper
{

    public static function generateClicks($limitQuery, $perc)
    {
        $smss = ClickhouseService::query("
        select * from sms_sendlogs_v where $limitQuery;");

        $insertClicks = [];
        foreach ($smss as $sms) {
            $res =
                ClickhouseService::query("select max(team_id) as team_id, max(phone_normalized) as phone_normalized from sms_sendlogs where sms_id = '{$sms['sms_id']}' group by sms_id");
            $insertClicks[] = [
                'team_id' => $res[0]['team_id'],
                'phone_normalized' => $res[0]['phone_normalized'],
                'sms_id' => $sms['sms_id'],
                'is_clicked' => rand(0, 99) < $perc ? 1 : 0,
                'updated_datetime' => microtime(true),
            ];
        }
        ClickhouseService::getClient()->insertAssocBulk('sms_sendlogs', $insertClicks);
    }
}