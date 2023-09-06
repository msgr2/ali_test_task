<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Laravel\Jetstream\Team as JetstreamTeam;

class Team extends JetstreamTeam
{
    use HasFactory,
        HasUuids;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'personal_team' => 'boolean',
        'meta' => 'array',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'personal_team',
    ];

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => TeamCreated::class,
        'updated' => TeamUpdated::class,
        'deleted' => TeamDeleted::class,
    ];

    public function lists(): hasMany
    {
        return $this->hasMany(Lists::class);
    }

    public function smsRoutingPlans()
    {
        return $this->hasMany(SmsRoutingPlan::class);
    }

    public function smsRoutingPlanConnectionsSeller()
    {
        return $this->hasManyThrough(SmsRoutePlatformConnection::class, SmsRoutingPlan::class);
    }

    public function smsRoutingPlanConnectionsCustomer()
    {
        return $this->hasMany(SmsRoutePlatformConnection::class, 'customer_team_id');
    }

    public function smsRoutingPlatformRoutes()
    {
        return $this->hasManyThrough(CustomerRoute::class,
            SmsRoutePlatformConnection::class,
            'team_id',
            'platform_connection_id');
    }

    public function smsRoutes()
    {
        return $this->hasMany(SmsRoute::class);
    }

    public function smsRouteRates()
    {
        return $this->hasManyThrough(SmsRouteRate::class, SmsRoute::class);
    }
}
