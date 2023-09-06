<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JqFieldResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'field' => $this->resource['field'],
            'label' => $this->resource['label'],
            'type' => $this->resource['type'],
            /** @var array $operators */
            'operators' => $this->resource['operators'],
        ];
    }
}
