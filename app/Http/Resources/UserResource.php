<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at,
            'two_factor_confirmed_at' => $this->two_factor_confirmed_at,
            'profile_photo_url' => $this->profile_photo_url,
            'current_team_id' => $this->current_team_id,
            'current_team' => new TeamResource($this->currentTeam),
        ];
    }
}
