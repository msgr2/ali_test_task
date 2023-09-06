<?php

namespace Tests\Feature\Api;

use App\Models\Offer;
use Database\Factories\OfferFactory;
use Tests\TestCase;

class OffersTest extends BaseApiTest
{
    public function testOffersIndex()
    {
        $offer = Offer::factory()->state([
            'team_id' => $this->user->current_team_id
        ]);

        $response = $this->getJson('/api/v1/offers')->assertOk()->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'url',
                    'profit',
                    'created_at',
                    'updated_at',
                ]
            ]
        ]);

        $response->assertStatus(200);
    }

    public function testOffersStore()
    {
        $response = $this->postJson('/api/v1/offers', [
            'name' => 'Test Offer',
            'url' => 'https://example.com',
            'price' => 9.99,
        ])->assertCreated()->assertJsonStructure([
            'id',
            'name',
            'url',
            'profit',
            'created_at',
            'updated_at',
        ]);
    }

    public function testOffersUpdate()
    {
        $offer = Offer::factory()->state([
            'team_id' => $this->user->current_team_id
        ])->create();
        $response = $this->putJson("/api/v1/offers/{$offer->id}", [
            'name' => 'Test Offer',
            'url' => 'http://example.com',
            'profit' => 999,
        ])->assertOk()->assertJsonStructure([
            'id',
            'name',
            'url',
            'profit',
            'created_at',
            'updated_at',
        ])->assertJsonFragment([
            'name' => 'Test Offer',
            'url' => 'http://example.com',
            'profit' => 999,
        ]);

        $response->assertStatus(200);
    }

    public function testOffersDestroy()
    {
        $offer = Offer::factory()->state([
            'team_id' => $this->user->current_team_id
        ])->create();
        $this->deleteJson("/api/v1/offers/{$offer->id}")->assertNoContent();


        $this->getJson('/api/v1/offers')->assertOk()->assertJsonCount(0, 'data');
    }
}
