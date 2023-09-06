<?php

namespace App\Traits;

use App\Data\CampaignMultistepSettingsData;
use App\Data\CampaignMultistepStatusData;

trait HasMultistepCampaign
{
    const SETTINGS_KEY_MULTISTEP = 'multistep';

    public function getMultistepSettings(): ?CampaignMultistepSettingsData
    {
        return $this->getSettings()->multistep_settings;
    }

    public function hasMultistep()
    {
        return $this->getSettings()[self::SETTINGS_KEY_MULTISTEP] ?? false;
    }

    public function getMultistepStatus(): CampaignMultistepStatusData
    {
        $status = $this->getMetaByKey('multistep_status');
        if (!$status) {
            $status = CampaignMultistepStatusData::from();
            $this->addMeta('multistep_status', $status->toJson());
        }

        return CampaignMultistepStatusData::from($status);
    }

    public function setMultistepStatus(CampaignMultistepStatusData $data): void
    {
        $this->addMeta('multistep_status', $data->toArray());
    }

    public function setSettingsMultistep(CampaignMultistepSettingsData $multistepSettingsData)
    {
        $settings = $this->getSettings();
        $settings->multistep_settings = $multistepSettingsData;
        $this->setSettings($settings);
        $this->save();
    }
}