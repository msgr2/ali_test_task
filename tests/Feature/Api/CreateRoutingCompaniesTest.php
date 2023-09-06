<?php

namespace Tests\Feature\Api;

use Database\Factories\UserFactory;
use Tests\TestCase;

class CreateRoutingCompaniesTest extends TestCase
{
    public function testCreateCompany()
    {
        $user = UserFactory::new()->withSanctumToken()->withPersonalTeam()->create();
        $this->actingAs($user);
        $response = $this->postJson('/api/v1/sms/routing/companies', [
            'name' => 'test company',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'data' => ['name' => 'test company'],
        ]);
    }
}
