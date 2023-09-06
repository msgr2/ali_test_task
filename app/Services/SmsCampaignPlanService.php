<?php

namespace App\Services;

use App\Models\SmsCampaign;
use App\Models\SmsCampaignPlan;
use App\Models\SmsCampaignSenderid;
use App\Models\SmsCampaignText;
use Database\Factories\Clickhouse\ContactFactory;
use Illuminate\Support\Collection;
use Nnjeim\World\Models\Country;
use Nnjeim\World\Models\Timezone;

class SmsCampaignPlanService
{

    /**
     * @throws \Exception
     */
    public static function createCampaignCron(SmsCampaignPlan $plan): void
    {
        \Log::debug('Creating campaign cron for plan: ' . $plan->id);
        $settings = $plan->getSettings();
        foreach ($settings['countries'] as $country) {
//            if ($country['status'] != 'active') {
//                continue;
//            }
            $countryId = CountryService::guessCountry($country);
            $timezones = Timezone::where(['country_id' => $countryId])->get();
            if ($timezones->isEmpty()) {
                \Log::error('No timezones for country: ' . $countryId);
                continue;
            }
            foreach ($timezones as $timezone) {
                $starTime = explode('-', $settings['hours'])[0];
                if (!CountryService::timeHasPassed($timezone->name, $starTime)) {
                    continue;
                }

                $lastCampaign = SmsCampaign::where(['sms_campaign_plan_id' => $plan->id])
                    ->latest('created_at')
                    ->first();
                if (!empty($lastCampaign) && $lastCampaign->created_at->isToday()) {
                    continue;
                }
                \Log::debug('Creating campaign for plan: ' . $plan->id . ' and timezone: ' . $timezone->name);

                $segment = $settings['segment'];
                $contacts = self::getContactsBySegment($segment);
                if ($contacts->isEmpty()) {
                    \Log::warning('Segment has no contacts: ' . $segment . ' for plan: ' . $plan->id);
                }

                $campaign = SmsCampaign::create([
                    'sms_campaign_plan_id' => $plan->id,
                    'team_id' => $plan->team_id,
                    'name' => "Auto plan campaign",
                    'status' => 'pending',
                ]);

                foreach ($settings['senderids'] as $senderid) {
                    if ($senderid['is_active'] === false) {
                        continue;
                    }

                    SmsCampaignSenderid::create([
                        'sms_campaign_id' => $campaign->id,
                        'text' => $senderid['text'],
                        'is_active' => true,
                    ]);
                }

                foreach ($settings['texts'] as $text) {
                    if ($text['is_active'] === false) {
                        continue;
                    }

                    SmsCampaignText::create([
                        'sms_campaign_id' => $campaign->id,
                        'text' => $text['text'],
                        'is_active' => true,
                    ]);
                }

                foreach ($settings['offers'] as $offer) {
                    if ($offer['is_active'] === false) {
                        continue;
                    }

                    $campaign->offers()->attach($offer['id']);
                }

                $campaign->setSettings([
                    'country_id' => $countryId,
                    'timezone_name' => $timezone->name,
                    'routing_plan' => $settings['routing_plan'],
                    'segment' => $segment,
                    'max_sms_per_week_per_contact' => $settings['max_sms_per_week_per_contact'],
                    'autosender_settings' => [
                        'step_size' => 300,
                        'step_delay' => 120,
                        'start_time' => $starTime,
                        'end_time' => explode('-', $settings['hours'])[1],
                        'excluded_optimisations' => [

                        ],
                        'text_by_carrier' => true,
                        'notify_slack' => true,
                        'enrich_unknown_networks_mnp' => true,
                    ]
                ]);

                $campaign->save();
                SendCampaignService::send($campaign);
                \Log::debug('Created campaign: ' . $campaign->id . ' for plan: ' . $plan->id);
            }

            //get timezones of country
            //[create campaign]
            // V if has timezone in plan hours list and current day is in days list and not sent already
            // V today
            //.. and if segment per network has contacts create.
            //if created - save last created
            //
            //
        }
    }

    private static function getContactsBySegment(mixed $segment): Collection
    {
        return ContactFactory::new()->saveAndReturn();
    }
}
