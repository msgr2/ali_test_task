<?php

namespace Database\Factories;

use App\Models\SmsRoute;
use App\Models\SmsRouteCompany;
use App\Models\SmsRouteRate;
use App\Models\SmsRouteSmppConnection;
use App\Services\CountryService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Str;

class SmsRouteFactory extends Factory
{
    protected $model = SmsRoute::class;

    public function definition(): array
    {
        return [
            'name' => 'Route ' . rand(0, 99999),
            'sms_route_company_id' => SmsRouteCompany::factory()
                ->state(['team_id' => Str::uuid()->toString()])
                ->create()->id,
        ];
    }

    public function withSmppConnection()
    {
        return $this->afterCreating(function (SmsRoute $smsRoute) {
            $smsRoute->connection()->associate(SmsRouteSmppConnection::factory()->create())->save();
        });
    }

    public function withRouteRates()
    {
        return $this->has(SmsRouteRate::factory()
            ->sequence(['country_id' => CountryService::guessCountry('UK')],
                ['country_id' => CountryService::guessCountry('US')],
                ['country_id' => CountryService::guessCountry('AU')])
            ->count(3),
            'smsRouteRates');
    }
}
