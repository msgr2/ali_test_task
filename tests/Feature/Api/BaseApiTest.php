<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Auth\RequestGuard;
use Tests\TestCase;

class BaseApiTest extends TestCase
{
    protected $user;

    public function actingAsGuest(): void
    {
        $this->app['auth']->logout();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::new()->withPersonalTeam()->create();
        $this->actingAs($this->user);
        RequestGuard::macro('logout', function () {
            $this->user = null;
        });
    }
}