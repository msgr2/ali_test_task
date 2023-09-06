<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerRoute extends SmsRoute
{
    /**
     * @var SmsRoutePlatformConnection|\Illuminate\Database\Eloquent\HigherOrderBuilderProxy|mixed
     */
    public SmsRoutePlatformConnection $platformConnection;
    protected $table = 'sms_routes';

    public function getCustomerRouteName()
    {
        return "{$this->platformConnection->name}::{$this->name}";
    }

    public function smsRouteRates()
    {
        return $this->hasMany(SmsRouteRate::class, 'sms_route_id', 'id');
    }
}
