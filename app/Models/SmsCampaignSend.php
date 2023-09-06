<?php

namespace App\Models;

use App\Data\SmsCampaignSettingsData;
use App\Traits\HasMeta;
use App\Traits\HasMultistepCampaign;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsCampaignSend extends Model
{
    use HasMultistepCampaign;
    use HasUuids;
    use HasMeta;
    use SoftDeletes;

    protected $fillable = [
        'status',
        'meta'
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SmsCampaign::class, 'sms_campaign_id');
    }

    public function getLimit()
    {
        return 100;
    }

    public function getLists()
    {
        return $this->getMeta()['lists'];
    }

    public function getSettings(): ?SmsCampaignSettingsData
    {
        $settings = $this->getMeta()['settings'] ?? false;
        if (!$settings) {
            return null;
        }

        return SmsCampaignSettingsData::from($settings);
    }

}
