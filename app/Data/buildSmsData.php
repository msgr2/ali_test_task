<?php

namespace App\Data;

use App\Models\SmsCampaign;
use Spatie\LaravelData\Data;

class buildSmsData extends Data
{
    public function __construct(
        public string $phone_normalized,
        public string $name,
        public string $list_id,
        public string $sms_campaign_send_id,
        public string $sms_campaign_id,
        public string $team_id,
        public int    $counter,
    )
    {
    }

    public function getCampaign()
    {
        return SmsCampaign::find($this->campaign_id);
    }
}
