<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SmsCampaignLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'caller_type',
        'caller_id',
        'text',
        'meta',
    ];
}
