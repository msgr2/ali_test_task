<?php

namespace Database\Factories;

use App\Models\Domain;
use Illuminate\Database\Eloquent\Factories\Factory;

class DomainFactory extends Factory
{
    protected $model = Domain::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid,
            'domain' => $this->faker->domainName,
            'is_active' => $this->faker->boolean,
            'team_id' => null,
            'meta' => null,
        ];
    }
}
