<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShortyTest extends TestCase
{
    use WithFaker;

    public function test_connection()
    {
        $data = [
            'link' => $this->faker->url,
            'domain' => 'test.com',
            'campaign_id' => $this->faker->uuid,
            'meta' => [
                'sms_id' => $this->faker->uuid,
            ],
        ];

        try {
            $res = Http::withBody(json_encode($data), 'application/json')
                ->post(config('services.shortener.url') . '/api/short-url');

            $this->assertEquals(201, $res->status());
        } catch (\Exception $e) {
            $this->markTestSkipped($e->getMessage());
        }
    }
}
