<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self sms_campaign_send()
 */
class LogContextEnum extends Enum
{
    public static function sendCampaignContext()
    {
        return ['process' => LogContextEnum::sms_campaign_send()];
    }
}
