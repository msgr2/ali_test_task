<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class SmsCampaignSettingsData extends Data
{
    public function __construct(
        public int                            $send_amount,
        public ?array                         $segment_filters = [],
        public ?array                         $segment_ids = [],
        public ?string                        $sms_routing_plan_id = null,
        public ?string                        $send_time = null,
        public ?CampaignMultistepSettingsData $multistep_settings = null,
    )
    {
    }

    public static function getValidationRules(array $payload): array
    {
        $rules = parent::getValidationRules($payload);
        $dataRules = CampaignMultistepSettingsData::getValidationRules($payload);
        $rules['multistep_settings'] = 'nullable|array';
        foreach ($dataRules as $key => $value) {
            if (in_array('required', $value)) {
                $requiredKey = array_search('required', $value);
                $value[$requiredKey] = str_replace('required',
                    'required_if:multistep_settings,null',
                    $value[$requiredKey]);
            }


            $rules['multistep_settings.' . $key] = $value;
        }

        return $rules;
    }
}
