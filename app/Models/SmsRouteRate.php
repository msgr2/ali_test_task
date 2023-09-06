<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsRouteRate extends Model
{
    use SoftDeletes, HasFactory, HasUuids;

    protected $fillable = [
        'sms_route_id',
        'country_id',
        'rate',
        'meta'
    ];

    public function smsRoute()
    {
        return $this->belongsTo(SmsRoute::class);
    }
}
