<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_can_register(): void
    {
        $password = Uuid::uuid4();
        $response = $this->postJson('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertCreated();

        $this->postJson('/api/v1/token/login', [
            'email' => 'test@example.com',
            'password' => $password,
        ])->assertJsonStructure([
            'access_token',
        ])->assertOk();
//        $response->assertNoContent();
    }
}
