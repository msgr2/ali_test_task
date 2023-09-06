<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BalanceService;
use Tests\TestCase;

class BalanceTest extends TestCase
{
    public function testBalanceLogic()
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->assertEquals(0, BalanceService::getTeamBalance($user->current_team_id));
        $this->assertEquals(true, BalanceService::addBalance($user->current_team_id, 100, ['manually added']));
        $this->assertEquals(100, BalanceService::getTeamBalance($user->current_team_id));
        $this->assertEquals(true, BalanceService::deductBalance($user->current_team_id, 50, ['manually deducted']));
        $this->assertEquals(50, BalanceService::getTeamBalance($user->current_team_id));

    }
}
