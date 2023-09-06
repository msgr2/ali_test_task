<?php

namespace App\Http\Resources;

use App\Models\OfferCampaign;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OfferCampaign */
class OfferCampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'offer_id' => $this->offer_id,
            'sms_campaign_id' => $this->sms_campaign_id,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'is_deleted' => !empty($this->deleted_at),
        ];
    }
}
