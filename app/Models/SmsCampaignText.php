<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsCampaignText extends Model
{
    use SoftDeletes;
    use HasUuids;
    use HasFactory;

    protected $fillable = [
        'text',
        'sms_campaign_id'
    ];

    public function haveDomainOrOptoutTag()
    {
        return str_contains($this->text, '{domain}') || str_contains($this->text, '{optout}');
    }
}
