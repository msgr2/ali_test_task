<?php

namespace App\Http\Resources;

use App\Http\Controllers\SmsRoutingRoutesController;
use App\Models\SmsRouteSmppConnection;
use App\Traits\WhenMorphToLoaded;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SmsRoutingRouteResource extends JsonResource
{
    use WhenMorphToLoaded;

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sms_route_company_id' => $this->sms_route_company_id,
            'company' => new SmsRouteCompanyResource($this->whenLoaded('smsRouteCompany')),
            'connection' => $this->whenMorphToLoaded('connection', [
                SmsRouteSmppConnection::class => SmsRouteSmppConnectionResource::class,
            ]),
            'rates' => SmsRoutingRateResource::collection($this->whenLoaded('smsRouteRates')),
            'created_at' => $this->created_at,
            'links' => [
//                "edit" => action([SmsRoutingRoutesController::class, 'edit'], $this),
//                "update" => action([SmsRoutingRoutesController::class, 'update'], $this),
                "delete" => action([SmsRoutingRoutesController::class, 'destroy'], $this),
            ],
        ];
    }
}
