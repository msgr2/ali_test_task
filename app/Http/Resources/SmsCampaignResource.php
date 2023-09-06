<?php

namespace App\Http\Resources;

use App\Models\SmsCampaign;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SmsCampaign */
class SmsCampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'meta' => $this->meta,
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'sms_campaign_plan_id' => $this->sms_campaign_plan_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'offers_count' => $this->offers_count,
            'sends_count' => $this->sends_count,
            'team_id' => $this->team_id,
            'offers' => OfferResource::collection($this->whenLoaded('offers')),
        ];
    }
}
