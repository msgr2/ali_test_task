<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SmsRoutingLogs extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    public function smsRoute()
    {
        return $this->belongsTo(SmsRoute::class, 'sms_route_id');
    }
}
