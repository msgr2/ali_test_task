<?php

namespace Database\Factories\Clickhouse;

use Illuminate\Database\Eloquent\Factories\Factory;

class BalanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid,
            'team_id' => $this->faker->uuid,
            'balance' => $this->faker->randomFloat(2, 1, 1000),
        ];
    }
}
