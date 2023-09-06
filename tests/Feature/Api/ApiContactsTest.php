<?php

namespace Tests\Feature\Api;

use App\Models\Clickhouse\Contact;
use App\Services\CountryService;
use Laravel\Sanctum\Sanctum;

class ApiContactsTest extends BaseApiTest
{
    public function test_index_and_pagination()
    {
        $teamId = $this->user->current_team_id;
        $countryId = CountryService::guessCountry('uk');

        $contacts = Contact::factory()
            ->state(function (array $attributes) use ($teamId, $countryId) {
                return [
                    'team_id' => $teamId,
                    'country_id' => $countryId,
                ];
            })
            ->count(100)
            ->create();

        $res = $this->getJson('/api/v1/audience/contacts')->assertOk();
        $data = $res->json();
        $this->assertCount(25, $data['data']);
        $this->assertEquals(1, $data['meta']['page']);
        $this->assertEquals(25, $data['meta']['per_page']);
        $this->assertEquals(100, $data['meta']['total']);

        $res = $this->getJson('/api/v1/audience/contacts?page=2&per_page=50')->assertOk();
        $data = $res->json();
        $this->assertCount(50, $data['data']);
        $this->assertEquals(2, $data['meta']['page']);
        $this->assertEquals(50, $data['meta']['per_page']);
        $this->assertEquals(100, $data['meta']['total']);

        $res = $this->getJson('/api/v1/audience/contacts?page=3&per_page=50')->assertOk();
        $data = $res->json();
        $this->assertCount(0, $data['data']);
    }
}
