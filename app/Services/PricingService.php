<?php

namespace App\Services;

class PricingService
{
    private const COST_USER_CUSTOM_ROUTE = 0.0025; // 0.25 cents per sms

    public static function getCostOfCustomRoute($team_id)
    {
        return self::COST_USER_CUSTOM_ROUTE;
    }
}
