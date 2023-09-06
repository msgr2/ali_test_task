<?php

namespace App\Http\Resources;

use App\Models\SmsRoutingPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SmsRoutingPlan */
class SmsRoutingPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'team_id' => $this->team_id,
            'name' => $this->name,
//            'is_platform_plan' => $this->is_platform_plan,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'connections_count' => $this->connections_count,
            'sms_routes_count' => $this->sms_routes_count,
        ];
    }
}
