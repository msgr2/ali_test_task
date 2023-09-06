<?php

namespace App\Services\SendingProcess\Routing;

use App\Data\SmsRoutingPlanSelectedData;
use App\Data\SmsRoutingPlanSelectorData;
use App\Enums\SmsRoutingPlanRuleActionEnum;
use App\Enums\SmsRoutingPlanSelectedMethodEnum;
use App\Enums\SmsRoutingPlanSelectedStatusEnum;
use App\Models\SmsRoute;
use App\Services\CountryService;
use App\Services\PlatformRoutesService;
use App\Services\UserRoutesService;
use Illuminate\Support\Facades\Log;

class SmsRoutingPlanAutoSelectorService
{
    const AMOUNT_OF_SENT_LIMIT_FOR_STATS = 100;

    public static function selectRoute(SmsRoutingPlanSelectorData $selector)
    {
        $network = null;
        Log::debug("auto routing", ['country' => $selector->country_id, 'network' => $network, 'plan_id' =>
            $selector->plan_id]);

        $routes = UserRoutesService::getRoutes($selector->team_id, $selector->country_id, $network);
        $routes = $routes->filter(function ($route) {
            /** @var SmsRoute $route */
            return isset($route->priceForCountry);
        });
        $routes = $routes->filter(function ($route) use ($selector) {
            /** @var SmsRoute $route */
            return !in_array($route->id, $selector->filtered_route_ids);
        });
        $routes = $routes->filter(function ($route) {
            /** @var SmsRoute $route */
            return $route->is_active == true;
        });
        $routes = $routes->filter(function ($route) use ($selector) {
            /** @var SmsRoute $route */
            return $route->hasRateForCountry($selector->country_id);
        });

        if ($routes->isEmpty()) {
            return false;
        }
        $routes = $routes->values();
        $selectedRoute = $routes[$selector->counter % $routes->count()];
        return SmsRoutingPlanSelectedData::from([
            'selector_data' => $selector,
            'status' => SmsRoutingPlanSelectedStatusEnum::success(),
            'selected_action' => SmsRoutingPlanRuleActionEnum::send(),
            'selected_route_id' => $selectedRoute->id,
            'selected_method' => SmsRoutingPlanSelectedMethodEnum::auto(),
            'route_rate' => $selectedRoute->getRateForCountry($selector->country_id),
        ]);

        //todo in future add stats consideration

//        $routes = UserRoutesService::getAvailableRoutesForCountry(
//            $selector->plan->team_id,
//            $selector->country_id,
//            $network
//        );
//        dd($routes);
        return false;
        //todo add connected routes support.
        $routes['platform_routes'] = array_filter($routes['platform_routes'], function ($route) {
            return $route['status'] === 'active';
        });
        $routes['private_routes'] = array_filter($routes['private_routes'], function ($route) {
            return $route['status'] === 'active';
        });
        dd($routes);
        Log::debug("platform routes", [
            'count' => count($routes),
            'ids' => ArrayHelper::getColumn($routes, 'id')
        ]);

        if (empty($routes)) {
            Log::warning('no platform routes but auto routing..');
            return null;
        }

        $weeklyStats = self::getWeeklyRouteStats($routes);
        $missingRoutes = self::missingRoutesInStats($weeklyStats, $routes);
        if (count($missingRoutes)) {
            $selectedGatewayId = $missingRoutes[array_rand($missingRoutes)];
            $platformRoute = self::getSelectedRouteByGatewayId($routes, $selectedGatewayId);
            $selector->setByAuto($platformRoute, SelectorMethodEnum::SELECT_METHOD_AUTO_MISSING);
            return true;
        }
        $selectedGatewayId = self::chooseRouteByStats($weeklyStats,
            $selector->request->plan->routing_plan_selection_method);
        if ($selectedGatewayId) {
            $platformRoute = self::getSelectedRouteByGatewayId($routes, $selectedGatewayId);
            $selector->setByAuto($platformRoute, SelectorMethodEnum::SELECT_METHOD_AUTO_PERF);
            return true;
        }

        return null;
    }

    private static function getWeeklyRouteStats(array $platformRoutes)
    {
        if (!App::isDev()) {
            throw new Exception('Need to be implemented before production');
        }
        return [
            [
                'gateway_id' => Demo::NZ_ROUTE_ID,
                'sent' => 1000,
                'sms_cost' => 0.01,
                'send_cost' => 3,
                'clicks' => 1,
                'ctr' => 0.1,
                'cpc' => 3
            ],
            [
                'gateway_id' => Demo::NZ_ROUTE2_ID,
                'sent' => 10,
                'sms_cost' => 0.02,
                'cost' => 1,
                'clicks' => 0,
                'ctr' => 0,
                'cpc' => 9999
            ]
        ];
    }

    private static function missingRoutesInStats(array $last24Stats, array $platformRoutes)
    {
        return [Demo::NZ_ROUTE_ID];
    }

    private static function getSelectedRouteByGatewayId($platformRoutes, $gatewayId)
    {
        foreach ($platformRoutes as $platformRoute) {
            if ($platformRoute->id == $gatewayId) {
                return $platformRoute;
            }
        }

        return null;
    }

    private static function chooseRouteByStats(array $weeklyStats, $preferred_selection_method)
    {
        Log::debug(
            "choosing by route stats",
            ['method' => $preferred_selection_method]
        );
        if (!in_array(
            $preferred_selection_method,
            PlanSelectMethodEnum::getConstantsByValue()
        )) {
            throw new Exception('Unknown selection method');
        }
        if (empty($weeklyStats)) {
            return null;
        }

        $maxRow = [];
        foreach ($weeklyStats as $row) {
            if (empty($maxRow)) {
                $maxRow = $row;
            }

            if ($row[$preferred_selection_method] > $maxRow[$preferred_selection_method]) {
                $maxRow = $row;
            }
        }

        Log::debug("selected", ['row' => $row]);
        return $maxRow['gateway_id'];
    }

    private static function getMonthlyRouteStats(array $platformRoutes)
    {
        return [
            [
                'gateway_id' => Demo::NZ_ROUTE_ID,
                'sent' => 10000,
                'cost' => 3,
                'clicks' => 1,
                'ctr' => 0.1,
                'cpc' => 3
            ],
            [
                'gateway_id' => Demo::NZ_ROUTE2_ID,
                'sent' => 10,
                'cost' => 1,
                'clicks' => 0,
                'ctr' => 0,
                'cpc' => 9999
            ]
        ];
    }

    private static function getDailyRouteStats(array $platformRoutes)
    {
        return [];
    }

}
