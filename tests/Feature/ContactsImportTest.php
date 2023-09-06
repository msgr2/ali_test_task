<?php

namespace Tests\Feature;

use App\Imports\ContactsImport;
use App\Models\Clickhouse\Views\ContactSmsView;
use App\Models\Clickhouse\Views\ContactTagView;
use App\Models\DataFile;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\File;
use PhpClickHouseLaravel\RawColumn;
use Tests\Feature\Api\BaseApiTest;

class ContactsImportTest extends BaseApiTest
{
    use WithFaker;

    public function test_numbers_import_xlsx()
    {
        $fileName = 'demo_list-20.xlsx';
        $this->copySampleFile($fileName);
        $teamId = $this->user->current_team_id;

        $dataFile = DataFile::factory()
            ->state(function (array $attributes) use ($fileName, $teamId) {
                return [
                    'team_id' => $teamId,
                    'file_name' => $fileName,
                    'meta' => array_merge($attributes['meta'] ?? [], [
                        'logical_test' => true,
                    ]),
                ];
            })
            ->create();

        $contacts = new ContactsImport($dataFile);
        $contacts->import();

        $data = ContactSmsView::where('team_id', $teamId)
            ->getRows();

        $this->assertCount(20, $data);
    }

    public function test_numbers_tags()
    {
        $fileName = 'demo_list-custom-fields.csv';
        $this->copySampleFile($fileName);

        $tag1 = $this->faker->word;
        $tag2 = $this->faker->word;
        $teamId = $this->user->current_team_id;

        $dataFile = DataFile::factory()
            ->state(function (array $attributes) use ($fileName, $teamId, $tag1, $tag2) {
                return [
                    'team_id' => $teamId,
                    'file_name' => $fileName,
                    'meta' => array_merge($attributes['meta'] ?? [], [
                        'logical_test' => true,
                        'tags' => [$tag1, $tag2],
                    ]),
                ];
            })
            ->create();

        $contacts = new ContactsImport($dataFile);
        $contacts->import();

        $tags = ContactTagView::where('team_id', $teamId)
            ->whereIn('tag', [$tag1, $tag2])
            ->getRows();

        $this->assertCount(40, $tags);
        $this->assertCount(20,
            array_filter($tags, function ($tag) use ($tag1) {
                return $tag['tag'] === $tag1;
            }));
    }

    public function test_numbers_import_custom_fields()
    {
        $fileName = 'demo_list-custom-fields.csv';
        $this->copySampleFile($fileName);

        $teamId = $this->user->current_team_id;

        $dataFile = DataFile::factory()
            ->state(function (array $attributes) use ($fileName, $teamId) {
                return [
                    'team_id' => $teamId,
                    'file_name' => $fileName,
                    'meta' => array_merge($attributes['meta'] ?? [], [
                        'logical_test' => true,
                        'columns' => [
                            'number' => 0,
                            'country' => 1,
                            'custom1_str' => 2,
                            'custom2_str' => 3,
                            'custom1_int' => 4,
                            'custom2_int' => 5,
                            'custom1_dec' => 6,
                            'custom2_dec' => 7,
                            'custom1_datetime' => 8,
                            'custom2_datetime' => 9,
                        ],
                    ]),
                ];
            })
            ->create();

        $import = new ContactsImport($dataFile);
        $import->import();

        $rows = ContactSmsView::where('team_id', $teamId)
            ->getRows();

        $this->assertCount(20, $rows);

        foreach ($rows as $row) {
            $this->assertNotEmpty($row['custom1_str']);
//            $this->assertNotEmpty($row['custom2_str']);
            $this->assertNotEmpty($row['custom1_int']);
//            $this->assertNotEmpty($row['custom2_int']);
            $this->assertNotEmpty($row['custom1_dec']);
//            $this->assertNotEmpty($row['custom2_dec']);
            $this->assertNotEmpty($row['custom1_datetime']);
//            $this->assertNotEmpty($row['custom2_datetime']);
        }
    }

    public function testAutoDetectDelimiter()
    {
        $fileName = 'demo_list-auto-detect-delimiter.csv';
        $this->copySampleFile($fileName);

        $teamId = $this->user->current_team_id;

        $dataFile = DataFile::factory()
            ->state(function (array $attributes) use ($fileName, $teamId) {
                return [
                    'team_id' => $teamId,
                    'file_name' => $fileName,
                    'meta' => array_merge($attributes['meta'] ?? [], [
                        'logical_test' => true,
                    ]),
                ];
            })
            ->create();

        $import = new ContactsImport($dataFile);

        $this->assertEquals(';', $import->getDelimiter());

        $import->import();

        $data = ContactSmsView::where('team_id', $teamId)
            ->getRows();

        $this->assertCount(20, $data);
    }

    private function copySampleFile($sample): string
    {
        $path = __DIR__ . '/data/' . $sample;
        $targetPath = storage_path('app/teams/' . $this->user->current_team_id . '/data-files');
        $targetFile = $targetPath . '/' . $sample;
        File::makeDirectory($targetPath, 0775, true, true);
        File::copy($path, $targetFile);

        return $targetFile;
    }
}
