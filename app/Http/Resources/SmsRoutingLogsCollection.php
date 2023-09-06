<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/** @see \App\Models\SmsRoutingLogs */
class SmsRoutingLogsCollection extends ResourceCollection
{
    public $collects = SmsRoutingLogsResource::class;

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }
}
