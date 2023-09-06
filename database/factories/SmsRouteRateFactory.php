<?php

namespace Database\Factories;

use App\Models\SmsRouteRate;
use App\Services\CountryService;
use Illuminate\Database\Eloquent\Factories\Factory;

class SmsRouteRateFactory extends Factory
{
    protected $model = SmsRouteRate::class;

    public function definition(): array
    {
        return [
            'sms_route_id' => $this->faker->uuid(),
            'country_id' => CountryService::guessCountry($this->faker->countryCode()),
            'rate' => $this->faker->randomFloat(2, 0, 1),
        ];
    }
}
