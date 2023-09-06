<?php

namespace App\Services;

use App\Models\Clickhouse\Balance;
use Exception;
use Str;

class BalanceService
{
    public static function addBalance($team_id, float $amount, $meta)
    {
        $balance = Balance::create([
            'id' => Str::uuid(),
            'team_id' => $team_id,
            'amount' => $amount,
            'meta' => json_encode($meta),
        ]);
        if (!$balance->id) {
            throw new Exception('Balance not created');
        }

        return true;
    }

    public static function getTeamBalance(
        $team_id
    )
    {
        $db = ClickhouseService::getClient();
        return $db
            ->select('SELECT sum(balance) as balance FROM balances_teams_v where team_id = :team',
                ['team' => $team_id])->fetchOne('balance');
    }

    public static function deductBalance(
        $team_id,
        float $amount,
        $meta
    )
    {
        $amount = $amount > 0 ? -$amount : $amount;

        $balance = Balance::create([
            'id' => Str::uuid(),
            'team_id' => $team_id,
            'amount' => $amount,
            'meta' => json_encode($meta),
        ]);
        if (!$balance->id) {
            throw new Exception('Balance not created');
        }

        return true;
    }
}
