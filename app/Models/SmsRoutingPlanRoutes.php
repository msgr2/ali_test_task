<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsRoutingPlanRoutes extends Model
{
    use SoftDeletes, HasFactory, HasUuids;

    protected $fillable = [
        'sms_route_id',
        'sms_routing_plan_id',
        'customer_id',
        'priority',
    ];
}
