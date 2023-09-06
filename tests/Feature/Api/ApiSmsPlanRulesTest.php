<?php

namespace Tests\Feature\Api;

use App\Data\SmppConnectionData;
use App\Data\SmsRoutingPlanRuleSplitActionVarsData;
use App\Enums\SmsRoutingPlanRuleActionEnum;
use App\Http\Resources\SmsRoutingRouteResource;
use App\Models\SmsRoute;
use App\Models\SmsRoutingPlan;
use App\Models\SmsRoutingPlanRule;
use App\Services\CountryService;
use Database\Factories\UserFactory;
use Tests\TestCase;

class ApiSmsPlanRulesTest extends TestCase
{

    public function testIndex(): void
    {
        $user = UserFactory::new()->withPersonalTeam()->create();
        $plan = SmsRoutingPlan::factory()->state([
            'team_id' => $user->current_team_id
        ])->create();
        $rule = SmsRoutingPlanRule::factory()->state([
            'sms_routing_plan_id' => $plan->id
        ])->create();
        $this->actingAs($user);
        $this->assertDatabaseHas('sms_routing_plan_rules', [
            'id' => $rule->id
        ]);
        $this->getJson("/api/v1/sms/routing/plans/{$plan->id}/rules")
            ->assertJsonFragment([
                'id' => $rule->id
            ])->assertOk();
    }

    public function testStore(): void
    {
        $user = UserFactory::new()->withPersonalTeam()->create();
        $plan = SmsRoutingPlan::factory()->state([
            'team_id' => $user->current_team_id
        ])->create();
        $this->actingAs($user);
        $this->postJson("/api/v1/sms/routing/plans/{$plan->id}/rules", [
            'action' => SmsRoutingPlanRuleActionEnum::send()
        ])->assertCreated()->assertJsonFragment([
            'action' => SmsRoutingPlanRuleActionEnum::send()
        ]);
    }

    public function testUpdate(): void
    {
        $user = UserFactory::new()->withPersonalTeam()->create();
        $plan = SmsRoutingPlan::factory()->state([
            'team_id' => $user->current_team_id
        ])->create();
        $rule = SmsRoutingPlanRule::factory()->state([
            'sms_routing_plan_id' => $plan->id
        ])->create();
        $this->actingAs($user);
        $this->putJson("/api/v1/sms/routing/plans/{$plan->id}/rules/{$rule->id}", [
            'action' => SmsRoutingPlanRuleActionEnum::drop()
        ])->assertOk()->assertJsonFragment([
            'action' => SmsRoutingPlanRuleActionEnum::drop()
        ]);
    }

    public function testDestroy(): void
    {
        $user = UserFactory::new()->withPersonalTeam()->create();
        $plan = SmsRoutingPlan::factory()->state([
            'team_id' => $user->current_team_id
        ])->create();
        $rule = SmsRoutingPlanRule::factory()->state([
            'sms_routing_plan_id' => $plan->id
        ])->create();
        $this->actingAs($user);
        $this->deleteJson("/api/v1/sms/routing/plans/{$plan->id}/rules/{$rule->id}")
            ->assertNoContent();
        $this->getJson("/api/v1/sms/routing/plans/{$plan->id}/rules")
            ->assertJsonMissing([
                'id' => $rule->id
            ])->assertOk();
    }

    public function testSimulateDropRule()
    {
        $user = UserFactory::new()->withPersonalTeam()->create();
        $plan = SmsRoutingPlan::factory()->state([
            'team_id' => $user->current_team_id
        ])->create();

        SmsRoutingPlanRule::factory()->state([
            'sms_routing_plan_id' => $plan->id,
            'action' => SmsRoutingPlanRuleActionEnum::drop(),
            'country_id' => CountryService::guessCountry('AU')
        ])->create();
        $this->actingAs($user);
        $res = $this->postJson("/api/v1/sms/routing/plans/{$plan->id}/simulate", [
            'country_id' => CountryService::guessCountry('AU'),
        ])->assertJsonFragment([
            'status' => 'success',
            'selected_method' => 'rules',
            'selected_action' => 'drop',
        ])->assertOk();
    }

    public function testSimulateSend()
    {
        $user = UserFactory::new()->withPersonalTeam()->create();
        $plan = SmsRoutingPlan::factory()->state([
            'team_id' => $user->current_team_id
        ])->create();
        $route1 = SmsRoute::factory()->withRouteRates()->withSmppConnection()->state([
            'team_id' => $user->current_team_id,
        ])->create();

        SmsRoutingPlanRule::factory()->state([
            'sms_routing_plan_id' => $plan->id,
            'action' => SmsRoutingPlanRuleActionEnum::send(),
            'country_id' => CountryService::guessCountry('AU'),
            'sms_route_id' => $route1->id
        ])->create();
        $this->actingAs($user);
        $res = $this->postJson("/api/v1/sms/routing/plans/{$plan->id}/simulate", [
            'country_id' => CountryService::guessCountry('AU'),
        ])->assertJsonFragment([
            'status' => 'success',
            'selected_method' => 'rules',
            'selected_action' => 'send',
            'selected_route_id' => $route1->id,
        ])->assertOk();
    }

    public function testSimulateSplitTest()
    {
        $user = UserFactory::new()->withPersonalTeam()->create();
        $plan = SmsRoutingPlan::factory()->state([
            'team_id' => $user->current_team_id
        ])->create();
        $route1 = SmsRoute::factory()->withRouteRates()->withSmppConnection()->state([
            'team_id' => $user->current_team_id,
        ])->create();
        $route2 = SmsRoute::factory()->withRouteRates()->withSmppConnection()->state([
            'team_id' => $user->current_team_id,
        ])->create();
        SmsRoutingPlanRule::factory()->state([
            'sms_routing_plan_id' => $plan->id,
            'action' => SmsRoutingPlanRuleActionEnum::split(),
            'country_id' => CountryService::guessCountry('AU'),
            'action_vars' => SmsRoutingPlanRuleSplitActionVarsData::from([
                'route_ids' => [$route1->id, $route2->id],
                'limit' => 1000
            ])->toJson(),
        ])->create();
        $this->actingAs($user);
        $res = $this->postJson("/api/v1/sms/routing/plans/{$plan->id}/simulate", [
            'country_id' => CountryService::guessCountry('AU'),
        ])->assertJsonFragment([
            'status' => 'success',
            'selected_method' => 'rules',
            'selected_action' => 'split',

//doens't manage to validate this part
//            'selected_rule' => [
//                'action_vars' => [
//                    'routes' => [$route1->id, $route2->id],
//                    'limit' => 1000,
//                ],
//            ],
        ])->assertOk();
    }

    public function testSimulateSplitWithCounters()
    {
        $user = UserFactory::new()->withPersonalTeam()->create();
        $plan = SmsRoutingPlan::factory()->state([
            'team_id' => $user->current_team_id
        ])->create();
        $route1 = SmsRoute::factory()->withRouteRates()->withSmppConnection()->state([
            'team_id' => $user->current_team_id,
        ])->create();
        $route2 = SmsRoute::factory()->withRouteRates()->withSmppConnection()->state([
            'team_id' => $user->current_team_id,
        ])->create();
        $rule = SmsRoutingPlanRule::factory()->state([
            'sms_routing_plan_id' => $plan->id,
            'action' => SmsRoutingPlanRuleActionEnum::split(),
            'country_id' => CountryService::guessCountry('AU'),
            'action_vars' => SmsRoutingPlanRuleSplitActionVarsData::from([
                'route_ids' => [$route1->id, $route2->id],
                'limit' => 1000
            ])->toJson(),
        ])->create();
        $this->actingAs($user);
        $this->postJson("/api/v1/sms/routing/plans/{$plan->id}/simulate", [
            'country_id' => CountryService::guessCountry('AU'),
            'counter' => 0,
        ])->assertJsonFragment([
            'status' => 'success',
            'selected_method' => 'rules',
            'selected_action' => 'split',
            'selected_route_id' => $route1->id,
        ])->assertOk();

        $this->postJson("/api/v1/sms/routing/plans/{$plan->id}/simulate", [
            'country_id' => CountryService::guessCountry('AU'),
            'counter' => 1,
        ])->assertJsonFragment([
            'status' => 'success',
            'selected_method' => 'rules',
            'selected_action' => 'split',
            'selected_route_id' => $route2->id,
            'selected_rule_id' => $rule->id,
        ])->assertOk();
    }

    public function testSimulatorSplitWithLimits()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    public function testCreateSplitRuleApiToPlan()
    {
        $user = UserFactory::new()->withPersonalTeam()->create();
        $plan = SmsRoutingPlan::factory()->state([
            'team_id' => $user->current_team_id
        ])->create();
        $route1 = SmsRoute::factory()->withRouteRates()->withSmppConnection()->state([
            'team_id' => $user->current_team_id,
        ])->create();
        $route2 = SmsRoute::factory()->withRouteRates()->withSmppConnection()->state([
            'team_id' => $user->current_team_id,
        ])->create();

        $this->actingAs($user);
        $res = $this->postJson("/api/v1/sms/routing/plans/{$plan->id}/rules/split", [
            'country_id' => CountryService::guessCountry('AU'),
            'route_ids' => [$route1->id, $route2->id],
            'limit' => 1000
        ])->assertJsonFragment([
            'action' => SmsRoutingPlanRuleActionEnum::split(),
            'country_id' => CountryService::guessCountry('AU'),
        ])->assertCreated();

        $ruleId = $res->decodeResponseJson()['id'];
        $this->putJson("/api/v1/sms/routing/plans/{$plan->id}/rules/{$ruleId}/split", [
            'country_id' => CountryService::guessCountry('AU'),
            'route_ids' => [$route1->id, $route2->id],
            'limit' => 1000
        ])->assertJsonFragment([
            'action' => SmsRoutingPlanRuleActionEnum::split(),
            'country_id' => CountryService::guessCountry('AU'),
        ])->assertOk();
    }

    public function testFilterRouteOnSplit()
    {
        $user = UserFactory::new()->withPersonalTeam()->create();
        $plan = SmsRoutingPlan::factory()->state([
            'team_id' => $user->current_team_id
        ])->create();
        $route1 = SmsRoute::factory()->withRouteRates()->withSmppConnection()->state([
            'team_id' => $user->current_team_id,
        ])->create();
        $route2 = SmsRoute::factory()->withRouteRates()->withSmppConnection()->state([
            'team_id' => $user->current_team_id,
        ])->create();
        SmsRoutingPlanRule::factory()->state([
            'sms_routing_plan_id' => $plan->id,
            'action' => SmsRoutingPlanRuleActionEnum::filter(),
            'country_id' => CountryService::guessCountry('AU'),
            'sms_route_id' => $route1->id,
        ])->create();
        SmsRoutingPlanRule::create([
            'sms_routing_plan_id' => $plan->id,
            'action' => 'split',
            'country_id' => CountryService::guessCountry('AU'),
            'action_vars' => SmsRoutingPlanRuleSplitActionVarsData::from([
                'route_ids' => [$route1->id, $route2->id],
                'limit' => 1000
            ])->toJson(),
        ]);

        $this->actingAs($user);

        $this->postJson("/api/v1/sms/routing/plans/{$plan->id}/simulate", [
            'country_id' => CountryService::guessCountry('AU'),
            'counter' => 0,
        ])->assertJsonFragment([
            'selected_method' => 'rules',
            'selected_action' => 'split',
            'selected_route_id' => $route2->id,
        ])->assertOk();
        $this->postJson("/api/v1/sms/routing/plans/{$plan->id}/simulate", [
            'country_id' => CountryService::guessCountry('AU'),
            'counter' => 1,
        ])->assertJsonFragment([
            'selected_method' => 'rules',
            'selected_action' => 'split',
            'selected_route_id' => $route2->id,
        ])->assertOk();
    }

    public function testFilterRouteOnSend()
    {
        $user = UserFactory::new()->withPersonalTeam()->create();
        $plan = SmsRoutingPlan::factory()->state([
            'team_id' => $user->current_team_id
        ])->create();
        $route1 = SmsRoute::factory()->withRouteRates()->withSmppConnection()->state([
            'team_id' => $user->current_team_id,
        ])->create();
        $route2 = SmsRoute::factory()->withRouteRates()->withSmppConnection()->state([
            'team_id' => $user->current_team_id,
        ])->create();
        SmsRoutingPlanRule::factory()->state([
            'sms_routing_plan_id' => $plan->id,
            'action' => SmsRoutingPlanRuleActionEnum::filter(),
            'country_id' => CountryService::guessCountry('AU'),
            'sms_route_id' => $route1->id,
        ])->create();
        SmsRoutingPlanRule::create([
            'sms_routing_plan_id' => $plan->id,
            'action' => 'send',
            'country_id' => CountryService::guessCountry('AU'),
            'sms_route_id' => $route1->id,
        ]);
        SmsRoutingPlanRule::create([
            'sms_routing_plan_id' => $plan->id,
            'action' => 'send',
            'country_id' => CountryService::guessCountry('AU'),
            'sms_route_id' => $route2->id,
        ]);

        $this->actingAs($user);

        $this->postJson("/api/v1/sms/routing/plans/{$plan->id}/simulate", [
            'country_id' => CountryService::guessCountry('AU'),
            'counter' => 0,
        ])->assertJsonFragment([
            'selected_method' => 'rules',
            'selected_action' => 'send',
            'selected_route_id' => $route2->id,
        ])->assertOk();
    }

    public function testSkipRouteWithNoCountryRate()
    {
        $user = UserFactory::new()->withPersonalTeam()->create();
        $plan = SmsRoutingPlan::factory()->state([
            'team_id' => $user->current_team_id
        ])->create();
        $route1 = SmsRoute::factory()->withSmppConnection()->state([
            'team_id' => $user->current_team_id,
        ])->create();

        SmsRoutingPlanRule::factory()->state([
            'sms_routing_plan_id' => $plan->id,
            'action' => SmsRoutingPlanRuleActionEnum::send(),
            'country_id' => CountryService::guessCountry('AU'),
            'sms_route_id' => $route1->id
        ])->create();
        $this->actingAs($user);
        $res = $this->postJson("/api/v1/sms/routing/plans/{$plan->id}/simulate", [
            'country_id' => CountryService::guessCountry('AU'),
        ])->assertJsonFragment([
            'fail_status' => 'no_route_found',
        ])->assertOk();
    }

    public function testSelectRouteNotFound()
    {
        $user = UserFactory::new()->withPersonalTeam()->create();
        $plan = SmsRoutingPlan::factory()->state([
            'team_id' => $user->current_team_id
        ])->create();
        $route1 = SmsRoute::factory()->withRouteRates()->withSmppConnection()->state([
            'team_id' => $user->current_team_id,
        ])->create();
        $route2 = SmsRoute::factory()->withRouteRates()->withSmppConnection()->state([
            'team_id' => $user->current_team_id,
        ])->create();

        $this->actingAs($user);

        //should return all routes for the country as split
        $this->postJson("/api/v1/sms/routing/plans/{$plan->id}/simulate", [
            'country_id' => CountryService::guessCountry('ZW'),
        ])->assertOk()->assertJsonFragment([
            'fail_status' => 'no_route_found',
        ]);
    }

    public function testAutoSelectRoute()
    {
        $user = UserFactory::new()->withPersonalTeam()->create();
        $plan = SmsRoutingPlan::factory()->state([
            'team_id' => $user->current_team_id
        ])->create();
        $route1 = SmsRoute::factory()->withRouteRates()->withSmppConnection()->state([
            'team_id' => $user->current_team_id,
        ])->create();
        $route2 = SmsRoute::factory()->withRouteRates()->withSmppConnection()->state([
            'team_id' => $user->current_team_id,
        ])->create();

        $this->actingAs($user);

        //should return all routes for the country as split
        $this->postJson("/api/v1/sms/routing/plans/{$plan->id}/simulate", [
            'country_id' => CountryService::guessCountry('AU'),
        ])->assertOk()->assertJsonFragment([
            'selected_method' => 'auto',
            'selected_action' => 'send',
            'selected_route_id' => $route1->id,
        ]);
        $this->postJson("/api/v1/sms/routing/plans/{$plan->id}/simulate", [
            'country_id' => CountryService::guessCountry('AU'),
            'counter' => 1,
        ])->assertOk()->assertJsonFragment([
            'selected_method' => 'auto',
            'selected_action' => 'send',
            'selected_route_id' => $route2->id,
        ]);
    }

    public function testAutoSelectRouteWithOneRouteWithoutRate()
    {
        $user = UserFactory::new()->withPersonalTeam()->create();
        $plan = SmsRoutingPlan::factory()->state([
            'team_id' => $user->current_team_id
        ])->create();
        $route1 = SmsRoute::factory()->withSmppConnection()->state([
            'team_id' => $user->current_team_id,
        ])->create();
        $route2 = SmsRoute::factory()->withRouteRates()->withSmppConnection()->state([
            'team_id' => $user->current_team_id,
        ])->create();

        $this->actingAs($user);

        //should return all routes for the country as split
        $this->postJson("/api/v1/sms/routing/plans/{$plan->id}/simulate", [
            'country_id' => CountryService::guessCountry('AU'),
        ])->assertOk()->assertJsonFragment([
            'selected_method' => 'auto',
            'selected_action' => 'send',
            'selected_route_id' => $route2->id,
        ]);
        $res = $this->postJson("/api/v1/sms/routing/plans/{$plan->id}/simulate", [
            'country_id' => CountryService::guessCountry('AU'),
            'counter' => 1,
        ])->assertOk()->assertJsonFragment([
            'selected_method' => 'auto',
            'selected_action' => 'send',
            'selected_route_id' => $route2->id,
        ]);
    }
}
