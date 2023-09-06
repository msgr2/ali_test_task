<?php

namespace App\Http\Resources;

use App\Models\SmsCampaignText;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SmsCampaignText */
class SmsCampaignTextResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sms_campaign_id' => $this->sms_campaign_id,
            'text' => $this->text,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
