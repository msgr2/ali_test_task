<?php

namespace App\Services;

use App\Models\SmsRoutingPlan;

class SmsRoutingPlanService
{
    public static function getDefaultRoutingPlan($team_id)
    {
        $default = SmsRoutingPlan::where(['team_id' => $team_id, 'is_team_default' => true])->first();
        if (!$default) {
            $plan = SmsRoutingPlan::where(['team_id' => $team_id])->first();
            if ($plan) {
                $plan->update(['is_team_default' => true]);
                return $plan;
            }
        }

        return false;
    }
}
