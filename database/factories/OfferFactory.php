<?php

namespace Database\Factories;

use App\Models\Offer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class OfferFactory extends Factory
{
    protected $model = Offer::class;

    public function definition(): array
    {
        return [
            'team_id' => $this->faker->uuid(),
            'name' => $this->faker->name(),
            'url' => $this->faker->url(),
            'profit' => $this->faker->randomNumber(4),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
