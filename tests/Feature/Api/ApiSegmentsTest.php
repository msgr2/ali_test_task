<?php

namespace Tests\Feature\Api;

use App\Enums\SegmentStatusEnum;
use App\Enums\SegmentTypeEnum;
use App\Models\Clickhouse\Contact;
use App\Models\Segment;
use Illuminate\Foundation\Testing\WithFaker;

class ApiSegmentsTest extends BaseApiTest
{
    use WithFaker;

    public function test_index_filter_type()
    {
        $segment = $this->createSegmentFactory();
        $res = $this->getJson('/api/v1/segments')->assertOk();
        $data = $res->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($segment->id, $data[0]['id']);

        $res = $this->getJson('/api/v1/segments?type=emails')->assertOk();
        $data = $res->json('data');
        $this->assertCount(0, $data);
    }

    public function test_cannot_store_invalid_rules()
    {
        $sample = Segment::factory()
            ->withNumbersSample2()
            ->make();
        $query = $sample->meta['query'];
        $query['rules'][0]['id'] = 'invalid_id';
        $query['rules'][0]['field'] = 'invalid_field';
        $query['rules'][0]['operator'] = 'invalid_operator';
        $query['rules'][0]['type'] = 'invalid_type';

        $data = [
            'name' => $this->faker->name,
            'type' => SegmentTypeEnum::numbers()->label,
            'query' => $query,
        ];

        $res = $this->postJson('/api/v1/segments', $data)->assertUnprocessable();
        $json = $res->json();

        $errors = [
//            'The rule id must be a valid id.',
            'The rule field must be a valid field.',
            'The rule operator must be a valid operator.',
//            'The rule type must be a valid type.',
        ];
        $this->assertEmpty(array_diff($errors, $json['errors']['query']));
    }

    public function test_store_valid_rules()
    {
        $sample = Segment::factory()
            ->withNumbersSample2()
            ->make();

        $data = [
            'name' => $this->faker->name,
            'type' => SegmentTypeEnum::numbers()->label,
            'query' => $sample->meta['query'],
        ];

        $res = $this->postJson('/api/v1/segments', $data)->assertCreated();

        $this->assertDatabaseHas('segments', [
            'name' => $data['name'],
            'type' => SegmentTypeEnum::numbers()->value,
            'meta' => json_encode([
                'query' => $data['query'],
            ]),
            'status_id' => SegmentStatusEnum::active()->value,
        ]);
    }

    public function test_update()
    {
        $segment = $this->createSegmentFactory();
        $sample = Segment::factory()
            ->withNumbersSample2()
            ->make();

        $data = [
            'name' => $this->faker->name,
            'type' => SegmentTypeEnum::numbers()->label,
            'query' => $sample->meta['query'],
        ];

        $res = $this->putJson("/api/v1/segments/{$segment->id}", $data)->assertOk();

        $this->assertDatabaseHas('segments', [
            'id' => $segment->id,
            'name' => $data['name'],
            'type' => SegmentTypeEnum::numbers()->value,
            'meta' => json_encode([
                'query' => $data['query'],
            ]),
            'status_id' => SegmentStatusEnum::active()->value,
        ]);
    }

    public function test_destroy()
    {
        $segment = $this->createSegmentFactory();
        $this->deleteJson("/api/v1/segments/{$segment->id}")->assertNoContent();

        $segment->refresh();
        $this->assertSoftDeleted($segment);
    }

    public function test_preview()
    {
        $teamId = $this->user->current_team_id;
        $sample = Segment::factory()
            ->state(function (array $attributes) use ($teamId) {
                return [
                    'team_id' => $teamId,
                ];
            })
            ->withNumbersSample1()
            ->make();

        $contacts = Contact::factory()
            ->state(function (array $attributes) use ($teamId) {
                return [
                    'team_id' => $teamId,
                    'country_id' => 225,
                ];
            })
            ->count(50)
            ->create();

        $data = [
            'type' => SegmentTypeEnum::numbers()->label,
            'query' => $sample->meta['query'],
        ];

        $res = $this->postJson('/api/v1/segments/preview', $data)->assertOk();
        $data = $res->json();

        $this->assertCount(10, $data['rows']);
        $this->assertEquals(50, $data['total']);
    }

    private function createSegmentFactory(): Segment
    {
        $teamId = $this->user->current_team_id;
        return Segment::factory()
            ->state(function (array $attributes) use ($teamId) {
                return [
                    'team_id' => $teamId,
                ];
            })
            ->withNumbersSample1()
            ->create();
    }
}
