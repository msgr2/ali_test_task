<?php

namespace App\Http\Resources;

use App\Enums\SegmentStatusEnum;
use App\Enums\SegmentTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Segment
 */
class SegmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => SegmentTypeEnum::from($this->type)->label,
            'name' => $this->name,
            'query' => $this->meta['query'] ?? [],
            'status' => SegmentStatusEnum::from($this->status_id)->label,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
