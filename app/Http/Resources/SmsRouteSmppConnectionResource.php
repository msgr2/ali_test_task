<?php

namespace App\Http\Resources;

use App\Models\SmsRouteSmppConnection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SmsRouteSmppConnectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'username' => $this->username,
            'password' => $this->password,
            'port' => $this->port,
            'dlr_url' => $this->dlr_url,
            'dlr_port' => $this->dlr_port,
            'workers_count' => $this->workers_count,
            'workers_delay' => $this->workers_delay,
            'created_at' => $this->created_at,
            'type' => 'SMPP',
        ];
    }
}
