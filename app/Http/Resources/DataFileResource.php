<?php

namespace App\Http\Resources;

use App\Enums\DataFileStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @mixin \App\Models\DataFile
 */
class DataFileResource extends JsonResource
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
            'name' => $this->name,
            'size' => $this->file_size,
            'status' => DataFileStatusEnum::from($this->status_id)->label,
            'created_at' => $this->created_at->toDateTimeString(),
            'created_ago' => Carbon::parse($this->created_at)->diffForHumans(),
            'columns' => $this->getColumns(),
        ];
    }
}
