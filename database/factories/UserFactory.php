<?php

namespace Database\Factories;

use App\Models\SmsRoute;
use App\Models\SmsRouteCompany;
use App\Models\SmsRoutePlatformConnection;
use App\Models\SmsRouteRate;
use App\Models\SmsRoutingPlan;
use App\Models\Team;
use App\Models\User;
use App\Services\CountryService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'remember_token' => Str::random(10),
            'profile_photo_path' => null,
            'current_team_id' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }

    /**
     * Indicate that the user should have a personal team.
     */
    public function withPersonalTeam(callable $callback = null): static
    {
        return $this->has(
            Team::factory()
                ->state(fn(array $attributes, User $user) => [
                    'name' => $user->name . '\'s Team',
                    'user_id' => $user->id,
                    'personal_team' => true,
                    'meta' => [
                        'domain_register' => [
                            'api_key' => config('testing.namecheap.api_key'),
                            'api_user' => config('testing.namecheap.api_user'),
                            'client_ip' => config('testing.namecheap.client_ip'),
                            'first_name' => 'John',
                            'last_name' => 'Doe',
                            'address1' => $this->faker->streetAddress(),
                            'city' => $this->faker->city(),
                            'state_province' => $this->faker->state(),
                            'postal_code' => $this->faker->postcode(),
                            'country' => $this->faker->countryCode(),
                            'phone' => substr_replace($this->faker->e164PhoneNumber(), '.', 4, 0),
                            'email' => $this->faker->email(),
                        ],
                    ],
                ])->afterCreating(
                    function (Team $team, User $user) {
                        $user->forceFill(['current_team_id' => $team->id])->save();
                    }
                ),
        );
    }

    public function asUkRouteSeller(Team $customerTeam): static
    {
        return $this->has(
            Team::factory()->has(
                SmsRoutingPlan::factory()->has(
                    SmsRoute::factory()->state(function (array $attributes, SmsRoutingPlan $plan) {
                        return [
                            'team_id' => $plan->team_id,
                            'name' => 'UK Platform Route',
                            'sms_route_company_id' => SmsRouteCompany::factory()->create([
                                'team_id' => $plan->team_id,
                            ])->id,
                        ];
                    })->has(
                        SmsRouteRate::factory()->state(function (array $atts, SmsRoute $route) {
                            return [
                                'sms_route_id' => $route->id,
                                'country_id' => CountryService::guessCountry('UK'),
                                'rate' => '0.01',
                            ];
                        })
                    )
                )->afterCreating(function (SmsRoutingPlan $plan) use ($customerTeam) {
                    SmsRoutePlatformConnection::create([
                        'sms_routing_plan_id' => $plan->id,
                        'customer_team_id' => $customerTeam->id,
                        'rate_multiplier' => 1.5,
                    ]);
                })
            )
        );
        return $this;
    }

    public function withSanctumToken(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->createToken('test-token');
        });
    }
}
