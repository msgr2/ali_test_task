<?php

namespace App\Http\Resources;

use App\Enums\SmsRoutingLogActionsEnum;
use App\Models\SmsRoutingLogs;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SmsRoutingLogs */
class SmsRoutingLogsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => SmsRoutingLogActionsEnum::from($this->action),
            'sms_route_id' => $this->sms_route_id,
            'country_id' => $this->country_id,
            'network_id' => $this->mobile_network_id,
            'old_rate' => $this->old_rate,
            'new_rate' => $this->new_rate,
            'user_id' => $this->user_id,
            'team_id' => $this->team_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
