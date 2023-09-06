<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SampleRowsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            /** @var array <array, array, string> */
            'rows' => $this->resource['rows'],
            /** @var int $cols */
            'cols' => $this->resource['cols'],
        ];
    }
}
