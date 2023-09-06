<?php

namespace Database\Factories;

use App\Models\SmsRouteSmppConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

class SmsRouteSmppConnectionFactory extends Factory
{
    protected $model = SmsRouteSmppConnection::class;

    public function definition(): array
    {
        return [
            'url' => '167.235.66.91',
            'username' => 'admin',
            'password' => 'admin',
            'port' => 2775,
        ];
    }
}
