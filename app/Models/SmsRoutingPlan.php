<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsRoutingPlan extends Model
{
    use SoftDeletes, HasFactory, HasUuids;

    protected $fillable = [
        'team_id',
        'name',
        'route_company_id',
        'meta'
    ];

    public function connections()
    {
        return $this->hasMany(SmsRoutePlatformConnection::class);
    }

    public function smsRoutes()
    {
        return $this->belongsToMany(SmsRoute::class, 'sms_routing_plan_routes');
    }

    public function planRules()
    {
        return $this->hasMany(SmsRoutingPlanRule::class, 'sms_routing_plan_rules');
    }
}
