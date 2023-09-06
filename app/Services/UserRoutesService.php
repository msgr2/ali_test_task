<?php

namespace App\Services;

use App\Models\CustomerRoute;
use App\Models\SmsRoute;
use App\Models\SmsRoutePlatformConnection;
use App\Models\SmsRouteSmppConnection;
use App\Models\SmsRoutingPlan;
use App\Models\SmsRoutingPlanRoutes;
use App\Models\User;
use Illuminate\Support\Collection;

class UserRoutesService
{

    public static function getAvailableRoutesForCountry($team_id, $country): array
    {
        return false;
        //"connected routes" not implemented yet
        $country = CountryService::guessCountry($country);
        $routes = self::getAvailableRoutes($team_id);
        $prices = [
            'private' => [],
            'platform' => [],
        ];
        foreach ($routes['private'] as $customRoutes) {
            /** @var SmsRoute $customRoutes */
            $rate = $customRoutes->smsRouteRates()->where(['country_id' => $country])->first();
            if ($rate) {
                $customRoutes->priceForCountry = $rate->rate;
                $prices['private'][] = $customRoutes;
            }
        }

        foreach ($routes['platform'] as $planRoutes) {
            foreach ($planRoutes['routes'] as $route) {
                if (self::setPlatformRate($route, $country, $planRoutes['connection'])) {
                    $prices['platform'][] = $route;
                }
            }
        }

        return $prices;
    }

    public static function getAvailableRoutes($team_id)
    {
        return false;
        $smppRoutes = SmsRoute::where(['connection_type' => SmsRouteSmppConnection::class])
            ->where(['team_id' => $team_id])->get();


        $planConnections = SmsRoutePlatformConnection::where(['customer_team_id' => $team_id])
            ->where(['is_active' => true])->get();
        $planRoutes = [];
        if ($planConnections->isNotEmpty()) {
            foreach ($planConnections as $i => $planConnection) {
                $planRoutes[$i]['connection'] = $planConnection;
                $routes = SmsRoutingPlanRoutes::where([
                    'sms_routing_plan_id' => $planConnections->pluck('sms_routing_plan_id')->all()
                ])->get();
                if ($routes->isNotEmpty()) {
                    $planRoutes[$i]['routes'] = CustomerRoute::where(
                        [
                            'id' => $routes->pluck('sms_route_id')->all()
                        ])->get();
                }

            }
        }

        return [
            'private' => $smppRoutes,
            'platform' => $planRoutes
        ];
    }

    private static function setPlatformRate(CustomerRoute $route, int $country, SmsRoutePlatformConnection $connection)
    {
        return false;
        $rate = $route->smsRouteRates()->where(['country_id' => $country])->first();
        if ($rate) {
            $route->priceForCountry = $rate->rate * $connection->rate_multiplier;
            $route->platformConnection = $connection;
            return true;
        }
        return false;
    }

    public static function getRoutes($team_id, $country_id = false): Collection
    {
        $routes = SmsRoute::where(['team_id' => $team_id])->where(['is_active' => true])->get();
        if ($country_id) {
            self::setRoutesRates($routes, $country_id);
        }

        return $routes;
    }

    private static function setRoutesRates($routes, $country_id): void
    {
        foreach ($routes as $route) {
            self::setRouteRate($route, $country_id);
        }
    }

    public static function setRouteRate($route, $country_id)
    {
        $rate = $route->smsRouteRates()->where(['country_id' => $country_id])->first();
        if ($rate) {
            $route->priceForCountry = $rate->rate;
            $route->setForCountry = $country_id;
        }

        return $route;
    }

}
