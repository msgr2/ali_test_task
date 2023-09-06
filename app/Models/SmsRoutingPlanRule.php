<?php

namespace App\Models;

use App\Data\SmsRoutingPlanRuleSplitActionVarsData;
use App\Enums\SmsRoutingPlanRuleActionEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;

/**
 * @property SmsRoutingPlan $plan
 * @property SmsRoute $smsRoute
 * @method bySelectOrder()
 */
class SmsRoutingPlanRule extends Model
{
    use SoftDeletes, HasUuids, HasFactory;

    public static array $rules;//
    protected $fillable = [
        'sms_route_id',
        'sms_routing_plan_id',
        'country_id',
        'network_id',
        'is_active',
        'priority',
        'action',
        'action_vars',
    ];

    public static function getRules()
    {
        return [
            'sms_route_id' => 'sometimes|uuid',
            'country_id' => 'sometimes|integer|exists:countries,id',
            'network_id' => 'sometimes|integer|exists:networks,id',
            'is_active' => 'sometimes|boolean',
            'priority' => 'sometimes|integer',
            /**
             * To create a split rule please check POST ./route/split endpoint.
             */
            'action' => ['required', Rule::in(SmsRoutingPlanRuleActionEnum::toArray())],
            'action_vars' => 'nullable|json',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SmsRoutingPlan::class, 'sms_routing_plan_id');
    }

    public function scopeBySelectOrder($query)
    {
        return $query->where('is_active', true)
            ->orderBy('country_id', 'asc')
            ->orderBy('network_id', 'asc')
            ->orderBy('priority', 'asc');
    }

    public function smsRoute()
    {
        return $this->hasOne(SmsRoute::class, 'id', 'sms_route_id');
    }

    public function network()
    {
        return $this->hasOne(MobileNetwork::class, 'id', 'network_id');
    }

    public function country()
    {
        return $this->hasOne(Country::class, 'id', 'country_id');
    }
}
