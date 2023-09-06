<?php

namespace App\Http\Resources;

use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Country */
class CountryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'iso' => $this->iso,
            'name' => $this->name,
            'nicename' => $this->nicename,
            'iso3' => $this->iso3,
            'numcode' => $this->numcode,
            'phonecode' => $this->phonecode,
//            'has_states' => $this->has_states,
//            'sender_id' => $this->sender_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
