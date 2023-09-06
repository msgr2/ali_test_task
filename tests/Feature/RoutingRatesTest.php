<?php

namespace Tests\Feature;

use App\Models\SmsRoute;
use App\Models\SmsRouteRate;
use App\Models\User;
use App\Services\CountryService;
use Tests\TestCase;

class RoutingRatesTest extends TestCase
{
    public function testAddRouteRate()
    {
        $user = User::factory()->withPersonalTeam()->create();
        $route = SmsRoute::factory()->withSmppConnection()->state([
            'team_id' => $user->current_team_id
        ])->create();
        $route->smsRouteRates()->saveMany([
            new SmsRouteRate([
                'rate' => 0.01,
                'country_id' => CountryService::guessCountry('US'),
                'sms_route_id' => $route->id,
            ]),
            new SmsRouteRate([
                'rate' => 0.02,
                'country_id' => CountryService::guessCountry('UK'),
                'sms_route_id' => $route->id,
            ]),
        ]);

        $this->assertEquals(0.01,
            $route->smsRouteRates()
                ->where('country_id', CountryService::guessCountry('US'))
                ->first()->rate);
    }


}
