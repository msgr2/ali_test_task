<?php

namespace App\Http\Resources;

use App\Models\SmsRoutingPlanRule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SmsRoutingPlanRule */
class SmsRoutingPlanRuleResource extends JsonResource
{
    public $resource = SmsRoutingPlanRule::class;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sms_route_id' => $this->sms_route_id,
            'sms_route' => SmsRoutingRouteResource::make($this->whenLoaded('smsRoute')),
            'sms_routing_plan_id' => $this->sms_routing_plan_id,
            'country_id' => $this->country_id,
            'country' => CountryResource::make($this->whenLoaded('country')),
            'network_id' => $this->network_id,
            'network' => MobileNetworkResource::make($this->whenLoaded('network')),
            'is_active' => $this->is_active,
            'priority' => $this->priority,
            'action' => $this->action,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'action_vars' => $this->action_vars
        ];
    }
}
