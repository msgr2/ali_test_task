<?php

namespace Tests\Feature\Api;

use Database\Factories\SmsRoutingPlanFactory;
use Database\Factories\UserFactory;
use Tests\TestCase;

class ApiRoutingPlansTest extends TestCase
{
    public function testCreateRoutingPlan()
    {
        $user = UserFactory::new()->withPersonalTeam()->create();
        $this->actingAs($user);
        $this->postJson('/api/v1/sms/routing/plans', [
            'name' => 'Test Plan',
        ])->assertCreated();
    }

    public function testGetRoutingPlans()
    {
        $user = UserFactory::new()->withPersonalTeam()->create();
        $this->actingAs($user);
        $plan = SmsRoutingPlanFactory::new()->state([
            'team_id' => $user->current_team_id
        ])->create();
        $this->getJson('/api/v1/sms/routing/plans')->assertJsonFragment([
            'name' => $plan->name,
        ])->assertOk();
    }

    public function testDeleteRoutingPlan()
    {
        $user = UserFactory::new()->withPersonalTeam()->create();
        $this->actingAs($user);
        $plan = SmsRoutingPlanFactory::new()->state([
            'team_id' => $user->current_team_id
        ])->create();
        $this->deleteJson('/api/v1/sms/routing/plans/' . $plan->id)->assertNoContent();
        $this->getJson('/api/v1/sms/routing/plans/' . $plan->id)->assertNotFound();
    }

    public function testUpdateRoutingPlan()
    {
        $user = UserFactory::new()->withPersonalTeam()->create();
        $this->actingAs($user);
        $plan = SmsRoutingPlanFactory::new()->state([
            'team_id' => $user->current_team_id
        ])->create();
        $this->putJson('/api/v1/sms/routing/plans/' . $plan->id, [
            'name' => 'New Name',
        ])->assertOk()->assertJsonFragment([
            'name' => 'New Name',
        ]);
    }

    public function testDeletedPlanNotShowingOnIndex()
    {
        $user = UserFactory::new()->withPersonalTeam()->create();
        $this->actingAs($user);
        $plan = SmsRoutingPlanFactory::new()->state([
            'team_id' => $user->current_team_id
        ])->create();
        $this->getJson('/api/v1/sms/routing/plans')->assertJson([
            'data' => [['name' => $plan->name]],
        ])->assertOk();
        $this->deleteJson('/api/v1/sms/routing/plans/' . $plan->id)->assertNoContent();
        $this->getJson('/api/v1/sms/routing/plans')->assertJsonMissing([
            'data' => [['name' => $plan->name]],
        ])->assertOk();
    }
}
