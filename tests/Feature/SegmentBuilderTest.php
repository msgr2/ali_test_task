<?php

namespace Tests\Feature;

use App\Models\Clickhouse\Contact;
use App\Models\Clickhouse\ContactTag;
use App\Models\Clickhouse\Materialized\ContactSmsMaterialized;
use App\Models\Segment;
use App\Models\User;
use App\Services\SegmentBuilderService;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Tests\TestCase;

class SegmentBuilderTest extends TestCase
{
    use WithFaker;

    public function test_segment_builder_sample1()
    {
        $segment = $this->createSegmentFactory(1);
        $builder = SegmentBuilderService::create($segment);
        $sql = $builder->toSql();

        $this->assertStringStartsWith('SELECT * FROM', $sql);
        $this->assertStringContainsString('greaterOrEquals(clicked_count, 0)', $sql);
        $this->assertStringContainsString('equals(country_id, 225)', $sql);
        $this->assertStringContainsString(
            "equals(toDate(date_created), parseDateTime32BestEffort('" . now()->toDateString() . "'))",
            $sql
        );

        $teamId = $segment->team_id;
        $contacts = Contact::factory()
            ->state(function (array $attributes) use ($teamId) {
                return [
                    'team_id' => $teamId,
                    'country_id' => 225,
                ];
            })
            ->count(10)
            ->create();

        $rows = $builder->getRows();

        $this->assertCount(10, $rows);
    }

    public function test_segment_builder_sample2()
    {
        $segment = $this->createSegmentFactory(2);
        $builder = SegmentBuilderService::create($segment);
        $sql = $builder->toSql();

        $this->assertStringStartsWith('SELECT * FROM', $sql);
        $this->assertStringContainsString('greater(clicked_count, 0)', $sql);
        $this->assertStringContainsString('equals(country_id, 225)', $sql);
        $this->assertStringContainsString("(equals(leads_count, 1) OR equals(sales_count, 1))", $sql);
        $this->assertStringContainsString(
            "equals(toDate(date_created), parseDateTime32BestEffort('" . now()->toDateString() . "'))",
            $sql
        );

        $teamId = $segment->team_id;
        $contacts = Contact::factory()
            ->state(function (array $attributes) use ($teamId) {
                return [
                    'team_id' => $teamId,
                    'country_id' => 225,
                ];
            })
            ->count(10)
            ->create();

        // add fake data
        $contacts = $contacts->take(5)->toArray();
        foreach ($contacts as $contact) {
            ContactSmsMaterialized::insertAssoc([
                [
                    'team_id' => $contact['team_id'],
                    'phone_normalized' => $contact['phone_normalized'],
                    'leads_count' => 1,
                    'clicked_count' => $this->faker->numberBetween(1, 10),
                ]
            ]);
        }

        $rows = $builder->getRows();

        $this->assertCount(5, $rows);
        $this->assertEmpty(array_diff(
            Arr::pluck($rows, 'phone_normalized'),
            Arr::pluck($contacts, 'phone_normalized'),
        ));
    }

    public function test_segment_builder_sample3()
    {
        $segment = $this->createSegmentFactory(3);
        $builder = SegmentBuilderService::create($segment);
        $sql = $builder->toSql();

        $this->assertStringStartsWith('SELECT * FROM', $sql);
        $this->assertStringContainsString('greater(clicked_count, 0)', $sql);
        $this->assertStringContainsString('SELECT `contact_id` FROM `contact_tags_view`', $sql);
        $this->assertStringContainsString("'user-tag-1'", $sql);
        $this->assertStringContainsString("'user-tag-2'", $sql);
        $this->assertStringContainsString("startsWith(tag, 'user')", $sql);

        $teamId = $segment->team_id;
        $contacts = Contact::factory()
            ->state(function (array $attributes) use ($teamId) {
                return [
                    'team_id' => $teamId,
                    'country_id' => 225,
                ];
            })
            ->count(10)
            ->create();

        // add fake data
        $contacts = $contacts->take(5)->toArray();
        foreach ($contacts as $contact) {
            ContactSmsMaterialized::insertAssoc([
                [
                    'team_id' => $contact['team_id'],
                    'phone_normalized' => $contact['phone_normalized'],
                    'clicked_count' => $this->faker->numberBetween(1, 10),
                ]
            ]);
            ContactTag::insertAssoc([
                [
                    'team_id' => $contact['team_id'],
                    'contact_id' => $contact['contact_id'],
                    'tag' => 'user-tag-1',
                ]
            ]);
        }

        $rows = $builder->getRows();

        $this->assertCount(5, $rows);
        $this->assertEmpty(array_diff(
            Arr::pluck($rows, 'phone_normalized'),
            Arr::pluck($contacts, 'phone_normalized'),
        ));
    }

    private function createSegmentFactory(int $sampleId): Segment
    {
        $user = User::factory()->withPersonalTeam()->create();
        return Segment::factory()
            ->state(function (array $attributes) use ($user) {
                return [
                    'team_id' => $user->current_team_id,
                ];
            })
            ->{"withNumbersSample" . $sampleId}()
            ->create();
    }
}
