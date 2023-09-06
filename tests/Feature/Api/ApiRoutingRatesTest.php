<?php

namespace Tests\Feature\Api;

use App\Models\SmsRoute;
use App\Models\SmsRouteRate;
use App\Models\User;
use App\Services\CountryService;
use Tests\TestCase;

class ApiRoutingRatesTest extends TestCase
{
    public function testAddRouteRate()
    {
        $user = User::factory()->withPersonalTeam()->create();
        $route = SmsRoute::factory()->withSmppConnection()->state([
            'team_id' => $user->current_team_id
        ])->create();

        $this->actingAs($user);
        $this->postJson('/api/v1/sms/routing/rates', [
            'rate' => 0.01,
            'country_id' => CountryService::guessCountry('US'),
            'sms_route_id' => $route->id,
        ])->assertCreated();
    }

    public function testUpdateRouteRate()
    {
        $user = User::factory()->withPersonalTeam()->create();
        $route = SmsRoute::factory()->withSmppConnection()->state([
            'team_id' => $user->current_team_id
        ])->create();

        $this->actingAs($user);
        $res = $this->postJson('/api/v1/sms/routing/rates', [
            'rate' => 0.01,
            'country_id' => CountryService::guessCountry('US'),
            'sms_route_id' => $route->id,
        ])->assertCreated();
        $createdRouteId = $res->json('id');

        $this->putJson('/api/v1/sms/routing/rates/' . $createdRouteId, [
            'rate' => 0.05
        ]);
        $this->assertDatabaseHas('sms_route_rates', [
            'id' => $createdRouteId,
            'rate' => 0.05
        ]);
        $this->getJson('/api/v1/sms/routing/rates/logs')->assertJsonFragment(
            [
                'action' => 'update_rate',
                'sms_route_id' => $route->id,
                'country_id' => CountryService::guessCountry('US'),
                'old_rate' => "0.01",
                'new_rate' => "0.05",
                'team_id' => $user->current_team_id,
                'user_id' => $user->id,
            ]
        );
    }

    public function testRouteRateIndex()
    {
        $user = User::factory()->withPersonalTeam()->create();
        $route = SmsRoute::factory()->withSmppConnection()->state([
            'team_id' => $user->current_team_id
        ])->create();

        SmsRouteRate::factory()->state([
            'sms_route_id' => $route->id,
            'country_id' => CountryService::guessCountry('US'),
        ])->create();

        $this->actingAs($user);
        $this->getJson('/api/v1/sms/routing/rates')
            ->assertOk()->assertJsonStructure([
                'data' => [
                    '*' => [
                        'country_id',
                        'rate',
                        'sms_route_id',
                        'sms_route'
                    ]
                ]
            ]);
    }

    public function testRouteRateLog()
    {
        $user = User::factory()->withPersonalTeam()->create();
        $route = SmsRoute::factory()->withSmppConnection()->state([
            'team_id' => $user->current_team_id
        ])->create();

        $this->actingAs($user);
        $this->postJson('/api/v1/sms/routing/rates', [
            'rate' => 0.01,
            'country_id' => CountryService::guessCountry('US'),
            'sms_route_id' => $route->id,
        ])->assertCreated();

        $this->getJson('/api/v1/sms/routing/rates/logs')
            ->assertOk()->assertJsonStructure([
                'data' => [
                    '*' => [
                        'created_at',
                        'user_id',
                        'team_id',
                        'action',
                        'country_id',
                        'new_rate',
                        'old_rate',
                        'sms_route_id',
                    ]
                ]
            ]);
    }


}
