<?php

namespace App\Http\Resources;

use App\Http\Controllers\SmsRoutingRoutesController;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/** @see \App\Models\SmsRoute */
class SmsRoutingRouteCollection extends ResourceCollection
{
    public $collects = SmsRoutingRouteResource::class;

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'links' => [
                'store' => action([SmsRoutingRoutesController::class, 'store'])
            ],
        ];
    }
}
