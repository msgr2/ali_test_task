<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsCampaignSenderid extends Model
{
    use HasUuids;
    use SoftDeletes;
    use HasFactory;

    protected $fillable = [
        'text',
        'sms_campaign_id'
    ];
}
