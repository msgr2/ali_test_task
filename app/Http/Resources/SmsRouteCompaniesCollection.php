<?php

namespace App\Http\Resources;

use App\Http\Controllers\SmsRoutingCompaniesController;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/** @see \App\Models\SmsRouteCompany */
class SmsRouteCompaniesCollection extends ResourceCollection
{
    public $collects = SmsRouteCompanyResource::class;

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'links' => [
                'store' => [
                    'url' => action([SmsRoutingCompaniesController::class, 'store'], [], false),
                ],
            ],
        ];
    }
}
