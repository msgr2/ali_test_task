<?php

namespace App\Http\Resources;

use App\Models\Team;
use App\Services\BalanceService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Team */
class TeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'created_at' => $this->created_at,
            'balance' => BalanceService::getTeamBalance($this->id),
        ];
    }
}
