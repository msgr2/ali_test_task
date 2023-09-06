<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class CampaignSendToBuildSmsData extends Data
{

    public function __construct(
        public string  $contact_id,
        public int     $country_id,
        public string  $phone_normalized,
        public string  $name,
        public string  $sms_routing_plan_id,
        public string  $sms_campaign_send_id,
        public string  $sms_campaign_id,
        public string  $team_id,
        public int     $counter,
        public ?string $segment_id,
        public ?string $network_id, //todo
    )
    {

    }
}
