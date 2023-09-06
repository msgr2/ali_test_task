<?php

namespace App\Models;

use App\Services\UserRoutesService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class SmsRoute extends Model
{
    use SoftDeletes, HasFactory, HasUuids;

    public $priceForCountry;
    public $setForCountry;
    protected $fillable = [
        'team_id',
        'name',
        'sms_route_company_id',
    ];

    protected $hidden = ['meta'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        static::creating(function ($model) {
            if (empty($this->team_id) && auth()->check()) {
                $model->team_id = auth()->user()->currentTeam->id;
            }
        });

    }

    public function smppConnection(): MorphTo
    {
        return $this->morphTo('connection');
    }

    /**
     * @return MorphOne - SMPP or Highway connection
     */
    public function connection(): MorphTo
    {
        return $this->morphTo();
    }

    public function smsRouteRates()
    {
        return $this->hasMany(SmsRouteRate::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function smsRouteCompany(): belongsTo
    {
        return $this->belongsTo(SmsRouteCompany::class);
    }

    public function hasRateForCountry($country_id)
    {
        UserRoutesService::setRouteRate($this, $country_id);
        return $this->priceForCountry !== null;
    }

    public function getRateForCountry($country_id)
    {
        if ($this->setForCountry !== $country_id) {
            UserRoutesService::setRouteRate($this, $country_id);
        }
        return $this->priceForCountry;
    }
}
