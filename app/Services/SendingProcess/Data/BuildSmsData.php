<?php

namespace App\Services\SendingProcess\Data;

use App\Data\CampaignSendToBuildSmsData;
use App\Data\SmsRoutingPlanSelectedData;
use App\Models\Domain;
use App\Models\Offer;
use App\Models\SmsCampaignText;

class BuildSmsData
{
    public SmsCampaignText $selectedCampaignText;
    public $sms_shortlink;
    public $segment_id; //todo
    public CampaignSendToBuildSmsData $sendToBuildSmsData;
    /**
     * @var Offer|mixed
     */
    public Offer $selectedOffer;
    public Domain $domain;
    public $submited_text_parts;
    /**
     * @var true
     */
    public bool $is_initial_msg_long;
    public string $sms_id;
    public string $sms_optout_link;
    /**
     * @var array|string|string[]|null
     */
    public string|array|null $finalText;
    public int $final_text_msg_parts;
    public $final_text_is_unicode = false;

    public array $keyword_data = [];
    public string $scheme;
    public SmsRoutingPlanSelectedData $selectedRoute;
    /**
     * @var mixed|string
     */
    public string $selected_senderderid_id;
    public $selected_senderderid_text;

    public function getReplacementParams()
    {
        $fName = ucfirst(strtolower(explode(' ', $this->sendToBuildSmsData->name)[0]));
        $params = [
            'sms_campaign_id' => $this->sendToBuildSmsData->sms_campaign_id,
            'sms_campaign_text_id' => $this->selectedCampaignText->id,
            'phone' => $this->sendToBuildSmsData->phone_normalized,
//            'cost' => 0, //.. maybe in future
            'name' => $fName,
            'route_id' => $this->selectedRoute->selected_route_id,
            'contact_id' => $this->sendToBuildSmsData->contact_id,
        ];

        return $params;
    }

    public function getShortLink()
    {
        return $this->scheme . '://' . $this->keyword_data['url'];
    }

    public function getOptOutLink()
    {
        return $this->scheme . '://' . $this->keyword_data['opt-out'];
    }
}
