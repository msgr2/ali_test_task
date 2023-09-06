<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SmsCampaignAutosender extends Model
{
    use HasUuids;

    public $timestamps = false;

    public function smsCampaign()
    {
        return $this->belongsTo(SmsCampaign::class, 'sms_campaign_id');
    }
}
