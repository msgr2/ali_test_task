<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SmsRoutingRateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'country_id' => $this->country_id,
            'rate' => $this->rate,
            'sms_route_id' => $this->sms_route_id,
            'sms_route' => SmsRoutingRouteResource::make($this->smsRoute),
        ];
    }
}
