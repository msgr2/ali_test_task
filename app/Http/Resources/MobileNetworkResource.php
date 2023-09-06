<?php

namespace App\Http\Resources;

use App\Models\MobileNetwork;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MobileNetworkResource extends JsonResource
{
    public $resource = MobileNetwork::class;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mcc' => $this->mcc,
            'mnc' => $this->mnc,
            'country_name' => $this->country_name,
            'country_code' => $this->country_code,
            'country_id' => $this->country_id,
            'brand' => $this->brand,
            'operator' => $this->operator,
            'status' => $this->status,
        ];
    }
}
