<?php

namespace Tests\Feature;

use App\Models\SmsRoute;
use App\Models\SmsRouteCompany;
use App\Models\SmsRoutePlatformConnection;
use App\Models\SmsRouteRate;
use App\Models\SmsRouteSmppConnection;
use App\Models\SmsRoutingPlan;
use App\Models\SmsRoutingPlanRoutes;
use App\Models\User;
use App\Services\CountryService;
use App\Services\PlatformRoutesService;
use App\Services\UserRoutesService;
use Database\Factories\PlatformRouteFactory;
use Tests\TestCase;

class RoutingTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $this->user = $user;
        $user = User::factory()->withPersonalTeam()->create();
        $this->customer = $user;

    }

    public function testUserCanAddPrivateRoutes()
    {
        $this->markTestSkipped('Platform routes support is not implemented yet');
        $plan = SmsRoutingPlan::create([
            'team_id' => $this->user->currentTeam->id,
            'name' => 'Test Plan',
        ]);

        $company = SmsRouteCompany::create([
            'team_id' => $this->user->currentTeam->id,
            'name' => 'Test Company',
        ]);

        $route = SmsRoute::create([
            'team_id' => $this->user->currentTeam->id,
            'name' => 'Test Route',
            'sms_routing_plan_id' => $plan->id,
            'sms_route_company_id' => $company->id,
        ]);

        $connection = SmsRouteSmppConnection::create([
            'url' => '167.235.66.91',
            'username' => 'admin',
            'password' => 'admin',
            'port' => 2775,
        ]);
        $route->smppConnection()->associate($connection);
        $route->smsRouteCompany()->associate($company);
        $route->saveOrFail();

        $rate = SmsRouteRate::create([
            'sms_route_id' => $route->id,
            'country_id' => CountryService::guessCountry('UK'),
            'rate' => 0.01,
        ]);

        //get route rate for country
        SmsRoutingPlanRoutes::create([
            'sms_routing_plan_id' => $plan->id,
            'sms_route_id' => $route->id,
        ]);

        $routes = UserRoutesService::getAvailableRoutes($this->user);
        $this->assertNotEmpty($routes['private']);
        $this->assertEquals($routes['private'][0]->id, $route->id);
        $prices = UserRoutesService::getAvailableRoutesForCountry($this->user, 'UK');
        $this->assertEquals($prices['private'][0]->priceForCountry, 0.01);
        $route->refresh();
        $this->assertNotEmpty($route->smppConnection);
        $this->assertNotEmpty($route->smsRouteCompany);
    }

    public function testUserCanConnectPlatformRoutes()
    {
        $this->markTestSkipped('Platform routes support is not implemented yet');

        $plan = SmsRoutingPlan::create([
            'team_id' => $this->user->currentTeam->id,
            'name' => 'Test Plan',
        ]);

        $company = SmsRouteCompany::create([
            'team_id' => $this->user->currentTeam->id,
            'name' => 'Test Company',
        ]);

        $route = SmsRoute::create([
            'team_id' => $this->user->currentTeam->id,
            'name' => 'Test Route',
            'sms_routing_plan_id' => $plan->id,
            'sms_route_company_id' => $company->id,
        ]);

        $connection = SmsRouteSmppConnection::create([
            'url' => '167.235.66.91',
            'username' => 'admin',
            'password' => 'admin',
            'port' => 2775,
        ]);
        $route->smppConnection()->associate($connection);
        $route->saveOrFail();

        $rate = SmsRouteRate::create([
            'sms_route_id' => $route->id,
            'country_id' => CountryService::guessCountry('UK'),
            'rate' => 0.01,
        ]);

        //get route rate for country
        SmsRoutingPlanRoutes::create([
            'sms_routing_plan_id' => $plan->id,
            'sms_route_id' => $route->id,
        ]);

        //get available routes for user
        SmsRoutePlatformConnection::create([
            'sms_routing_plan_id' => $plan->id,
            'name' => 'SMSEdge',
            'customer_team_id' => $this->customer->current_team_id,
            'rate_multiplier' => 1.1,
        ]);

        $routes = UserRoutesService::getAvailableRoutes($this->customer);
        $this->assertEquals($routes['platform'][0]['routes'][0]->id, $route->id);
        $this->assertEquals($routes['platform'][0]['connection']->name, 'SMSEdge');

        $routes = UserRoutesService::getAvailableRoutesForCountry($this->customer, 'UK');
        $this->assertEquals($routes['platform'][0]->id, $route->id);
        $this->assertEquals($routes['platform'][0]->platformConnection->name, 'SMSEdge');
        $this->assertEquals(0.011, round($routes['platform'][0]->priceForCountry * 10000) / 10000);
        $this->assertEquals('SMSEdge::Test Route', $routes['platform'][0]->getCustomerRouteName());
    }


    public function testUserCanSeeWhichPlansHeSelling()
    {
        $this->markTestSkipped('Platform routes support is not implemented yet');

        $user = User::factory()->withPersonalTeam()->create();
        $customer = User::factory()->withPersonalTeam()->create();

        $plan = SmsRoutingPlan::create([
            'team_id' => $user->currentTeam->id,
            'name' => 'Test Plan',
        ]);

        $this->assertNotEmpty($plan->id);

        $company = SmsRouteCompany::create([
            'team_id' => $user->currentTeam->id,
            'name' => 'Test Company',
        ]);

        //assert not empty company id
        $this->assertNotEmpty($company->id);

        $route = SmsRoute::create([
            'team_id' => $user->currentTeam->id,
            'name' => 'Test Route',
            'sms_routing_plan_id' => $plan->id,
            'sms_route_company_id' => $company->id,
        ]);

        //assert not empty route id
        $this->assertNotEmpty($route->id);


        $connection = SmsRouteSmppConnection::create([
            'url' => '167.235.66.91',
            'username' => 'admin',
            'password' => 'admin',
            'port' => 2775,
        ]);

        //assert not empty connection id
        $this->assertNotEmpty($connection->id);

        $route->smppConnection()->associate($connection);
        $route->saveOrFail();

        $rate = SmsRouteRate::create([
            'sms_route_id' => $route->id,
            'country_id' => CountryService::guessCountry('UK'),
            'rate' => 0.01,
        ]);

        //assert not empty rate id
        $this->assertNotEmpty($rate->id);

        //get route rate for country
        $planroutes = SmsRoutingPlanRoutes::create([
            'sms_routing_plan_id' => $plan->id,
            'sms_route_id' => $route->id,
        ]);

        //assert not empty plan route id
        $this->assertNotEmpty($planroutes->id);

        //get available routes for user
        $platformConnection = SmsRoutePlatformConnection::create([
            'sms_routing_plan_id' => $plan->id,
            'name' => 'SMSEdge',
            'customer_team_id' => $customer->current_team_id,
            'rate_multiplier' => 1.1,
        ]);

        //assert not empty platform connection id
        $this->assertNotEmpty($platformConnection->id);


        $res = PlatformRoutesService::getCustomersConnectionForSeller($user);
        $this->assertEquals($res[0]->customer_team_id, $customer->current_team_id);
        $this->assertEquals($res[0]->is_active, true);
    }

    public function testRouteFactory()
    {
        $this->markTestSkipped('Platform routes support is not implemented yet');

        $customer = User::factory()->withPersonalTeam()->create();
        $seller = User::factory()->asUkRouteSeller($customer->currentTeam)->create();
        $routes = UserRoutesService::getAvailableRoutes($customer);
        $this->assertNotEmpty($routes['platform']);

        $prices = UserRoutesService::getAvailableRoutesForCountry($customer, 'UK');
        $this->assertEquals(0.015, round($prices['platform'][0]->priceForCountry * 10000) / 10000);
    }
}
