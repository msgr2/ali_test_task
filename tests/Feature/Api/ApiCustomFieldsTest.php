<?php

namespace Tests\Feature\Api;

use App\Enums\CustomFieldEnum;
use App\Models\CustomField;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

class ApiCustomFieldsTest extends BaseApiTest
{
    use WithFaker;

    public function test_index()
    {
        $teamId = $this->user->current_team_id;

        $customField = CustomField::factory()
            ->state(function (array $attributes) use ($teamId) {
                return [
                    'team_id' => $teamId,
                ];
            })
            ->create();

        $res = $this->getJson('/api/v1/custom-fields')->assertOk();
        $data = $res->json();

        $this->assertCount(1, $data['data']);
        $this->assertEquals($customField->id, $data['data'][0]['id']);
    }

    public function test_store()
    {
        $teamId = $this->user->current_team_id;
        $fieldKey = $this->faker->randomElement(CustomFieldEnum::toValues());

        $res = $this->postJson('/api/v1/custom-fields', [
            'field_name' => 'test field',
            'field_key' => $fieldKey,
        ])->assertCreated();

        $data = $res->json('data');

        $this->assertEquals('test field', $data['field_name']);
        $this->assertEquals($fieldKey, $data['field_key']);

        $this->assertDatabaseHas('custom_fields', [
            'id' => $data['id'],
            'team_id' => $teamId,
            'field_name' => 'test field',
            'field_key' => $fieldKey,
        ]);
    }

    public function test_store_trashed()
    {
        $teamId = $this->user->current_team_id;

        $customField = CustomField::factory()
            ->state(function (array $attributes) use ($teamId) {
                return [
                    'team_id' => $teamId,
                ];
            })
            ->create();
        $customField->delete();

        $res = $this->postJson('/api/v1/custom-fields', [
            'field_name' => 'test field',
            'field_key' => $customField['field_key'],
        ])->assertCreated();

        $data = $res->json('data');

        $this->assertEquals('test field', $data['field_name']);
        $this->assertEquals($customField['field_key'], $data['field_key']);

        $this->assertDatabaseHas('custom_fields', [
            'id' => $data['id'],
            'team_id' => $teamId,
            'field_name' => 'test field',
            'field_key' => $customField['field_key'],
        ]);

        $customField->refresh();
        $this->assertNull($customField->deleted_at);
    }

    public function test_update()
    {
        $teamId = $this->user->current_team_id;

        $customField = CustomField::factory()
            ->state(function (array $attributes) use ($teamId) {
                return [
                    'team_id' => $teamId,
                ];
            })
            ->create();

        $res = $this->putJson('/api/v1/custom-fields/' . $customField->id, [
            'field_name' => 'test field',
            'field_key' => $customField['field_key'],
        ])->assertOk();

        $data = $res->json('data');

        $this->assertEquals('test field', $data['field_name']);
        $this->assertEquals($customField['field_key'], $data['field_key']);

        $this->assertDatabaseHas('custom_fields', [
            'id' => $data['id'],
            'team_id' => $teamId,
            'field_name' => 'test field',
            'field_key' => $customField['field_key'],
        ]);
    }

    public function test_destroy()
    {
        $teamId = $this->user->current_team_id;

        $customField = CustomField::factory()
            ->state(function (array $attributes) use ($teamId) {
                return [
                    'team_id' => $teamId,
                ];
            })
            ->create();

        $this->deleteJson('/api/v1/custom-fields/' . $customField->id)
            ->assertNoContent();
    }
}
