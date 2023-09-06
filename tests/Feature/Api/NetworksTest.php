<?php

namespace Tests\Feature\Api;

use Database\Factories\UserFactory;
use Tests\TestCase;

class NetworksTest extends BaseApiTest
{
    public function testIndex(): void
    {
        $this->getJson('/api/v1/sms/routing/networks')
            ->assertOk();
    }
}
