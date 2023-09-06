<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OfferCampaign extends Model
{
    use SoftDeletes;

    public $incrementing = false;
    protected $primaryKey = null;
    protected $table = 'offer_campaign';
    protected $fillable = [
        'offer_id',
        'sms_campaign_id',
        'is_active',
    ];

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }
}
