<?php

namespace App\Http\Resources;

use App\Models\SmsCampaignAutosender;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SmsCampaignAutosender */
class SmsCampaignAutosenderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
        ];
    }
}
