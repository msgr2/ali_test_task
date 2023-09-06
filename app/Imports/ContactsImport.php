<?php

namespace App\Imports;

use App\Enums\DataFileStatusEnum;
use App\Exceptions\InvalidAttributesException;
use App\Models\Clickhouse\Contact;
use App\Models\Clickhouse\Views\ContactSmsView;
use App\Models\Clickhouse\Views\ContactTagView;
use App\Models\DataFile;
use App\Services\CountryService;
use App\Services\NumberService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;
use Ramsey\Uuid\Nonstandard\Uuid;

class ContactsImport
{
    protected DataFile $dataFile;
    protected string $filePath;
    protected array $columns;
    protected int $chunkIterator = 0;
    protected string $storagePath;
    protected string $delimiter = ',';
    protected array $countriesCache = [];

    public function __construct(DataFile $dataFile)
    {
        $this->dataFile = $dataFile;
        $this->columns = $dataFile->meta['columns'] ?? [];
        $this->storagePath = storage_path('app/teams/' . $this->dataFile->team_id . '/data-files');

        $this->autoDetectDelimiter();
    }

    public function lazyRead($chunkSize = 1000, $skip = 0): LazyCollection
    {
        return LazyCollection::make(function () {
            $handle = fopen($this->filePath, 'r');

            while (($row = fgetcsv($handle, 4096, $this->delimiter)) !== false) {
                yield $row;
            }

            fclose($handle);
        })
            ->skip($skip)
            ->chunk($chunkSize);
    }

    public function import($chunkSize = 1000, $skip = 0): LazyCollection
    {
        if (empty($this->columns)) {
            $this->setDataFileStatus(DataFileStatusEnum::failed());

            throw InvalidAttributesException::for(
                $this->dataFile::class,
                $this->dataFile->toArray(),
                [
                    'meta' => 'meta is empty or meta[columns] is empty'
                ]
            );
        }

        $this->setDataFileStatus(DataFileStatusEnum::processing());

        $this->log('Start import', [
            'columns' => $this->columns,
            'dataFile' => $this->dataFile->toArray(),
        ]);

        try {
            $this->filePath = $this->csvFilePath();

            $res = $this->lazyRead($chunkSize, $skip)
                ->each(function (LazyCollection $chunk) {
                    $records = $chunk->map(fn($row) => $this->prepareRow($row))
                        ->filter(fn($row) => $this->filterRow($row))
                        ->toArray();

                    if (!empty($records)) {
                        $this->saveChunk($records);
                    } else {
                        $this->log('Empty chunk after filter', [
                            'chunk' => $chunk->toArray(),
                        ]);
                    }
                });

            $this->setDataFileStatus(DataFileStatusEnum::completed());

            return $res;
        } catch (\Exception $e) {
            $this->setDataFileStatus(DataFileStatusEnum::failed());

            $this->log('Error while reading file', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 'error');

            throw $e;
        }
    }

    public function isLogicalTest(): bool
    {
        return $this->dataFile->meta['logical_test'] ?? false;
    }

    public function saveChunk(array $records): void
    {
        // store chunk to file
//        $pathInfo = pathinfo($this->filePath);
//        $fileName = $pathInfo['filename'] . '_chunk' . $this->chunkIterator . '.csv';
//        $filePath = $this->storagePath . '/chunks/' . $this->dataFile->id;
//
//        if (!file_exists($filePath)) {
//            mkdir($filePath, 0775, true);
//        }
//
//        $file = $filePath . '/' . $fileName;
//
//        $handle = fopen($file, 'w');
//
//        foreach ($records as $record) {
//            fwrite($handle, json_encode($record));
//        }
//
//        fclose($handle);

        $this->chunkIterator++;

        Contact::insertAssoc($records);
    }

    public function getDelimiter(): string
    {
        return $this->delimiter;
    }

    public function xls2csv(): string
    {
        $filePath = $this->storagePath . '/' . $this->dataFile->file_name;

        if (!file_exists($filePath)) {
            throw new \Exception('File not found');
        }

        if (!is_readable($filePath)) {
            throw new \Exception('File not readable');
        }

        $fileInfo = pathinfo($filePath);

        if (!in_array($fileInfo['extension'], ['xls', 'xlsx'])) {
            throw new \Exception('File is not xls');
        }

        $this->log('Start converting file to csv', [
            'file' => $filePath,
        ]);

        $csvFileName = $fileInfo['filename'] . '_' . time() . '.csv';

        if (!file_exists($this->storagePath)) {
            mkdir($this->storagePath, 0775, true);
        }

        $csvFilePath = $this->storagePath . '/' . $csvFileName;

        $reader = match ($fileInfo['extension']) {
            'xls' => new \PhpOffice\PhpSpreadsheet\Reader\Xls(),
            'xlsx' => new \PhpOffice\PhpSpreadsheet\Reader\Xlsx(),
        };
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
        $writer->setDelimiter(',');
        $writer->setEnclosure('');
        $writer->setLineEnding("\r\n");
        $writer->save($csvFilePath);

        $this->log('File converted', [
            'file' => $csvFilePath,
        ]);

        // assign to temp meta variable due to "Indirect modification of overloaded property" error
        $meta = $this->dataFile->meta;
        $meta['csv_file_name'] = $csvFileName;
        $this->dataFile->meta = $meta;
        $this->dataFile->saveOrFail();

        return $this->dataFile->meta['csv_file_name'];
    }

    public function getSampleRows(): array
    {
        $firstRows = $this->getFirstRows();

        if (empty($firstRows)) {
            return [];
        }

        $response = [
            'rows' => [],
            'cols' => 0,
        ];

        foreach ($firstRows as $row) {
            $arr = str_getcsv($row, $this->delimiter);
            $response['cols'] = max($response['cols'], count($arr));
            $response['rows'][] = $arr;
        }

        return $response;
    }

    public function prepareRow($row): array
    {
        $fields = [];
        $tags = [];

        if (!empty($row[$this->columns['country'] ?? -1])) {
            $fields['country_id'] = $this->getCountryId($row[$this->columns['country']]);
        }

        if (!empty($fields['country_id'])) {
            $fields['phone_normalized'] = $this->getNumber(
                $row[$this->columns['number'] ?? -1] ?? '',
                $fields['country_id']
            );
        }

        if (!empty($row[$this->columns['email'] ?? -1])) {
            $fields['email_normalized'] = $this->getEmail($row[$this->columns['email']]);
        }

        if (empty($fields['phone_normalized']) && empty($fields['email_normalized'])) {
            $this->log('Empty email or phone,country', [
                'row' => $row,
            ]);
            return [];
        }

        $isLogicalNumber = true;
        if (!empty($fields['phone_normalized']) && !empty($fields['country_id'])) {
            $isMobile = NumberService::isMobile($fields['phone_normalized'], $fields['country_id']);

            if ($this->isLogicalTest()) {
                $isLogicalNumber = NumberService::isLogicalNumber($fields['phone_normalized']);
            }

            $fields['phone_is_good'] = (int)($isMobile && $isLogicalNumber);
        }

        if (!empty($fields['email_normalized'])) {
            $fields['email_is_good'] = (int)filter_var($fields['email_normalized'], FILTER_VALIDATE_EMAIL);
        }

        if (!empty($row[$this->columns['name'] ?? -1])) {
            $fields['name'] = $row[$this->columns['name']];
        }

        if (!empty($row[$this->columns['foreign_id'] ?? -1])) {
            $fields['foreign_id'] = $row[$this->columns['foreign_id']];
        }

        if (!empty($this->dataFile->meta['tags'])) {
            $tags = array_unique($this->dataFile->meta['tags']);
        }

        $customStrArray = $this->getCustomStrArray($row, $this->columns);
        $customIntArray = $this->getCustomIntArray($row, $this->columns);
        $customDecArray = $this->getCustomDecArray($row, $this->columns);
        $customDatetimeArray = $this->getCustomDatetimeArray($row, $this->columns);

        $isNew = true;
        $number = null;
        $newContact = [
            'contact_id' => Uuid::uuid4()->toString(),
            'team_id' => $this->dataFile->team_id,
            ...$fields,
            ...$customStrArray,
            ...$customIntArray,
            ...$customDecArray,
            ...$customDatetimeArray,
        ];

        if (!empty($newContact['phone_normalized'])) {
            $number = ContactSmsView::where('team_id', $this->dataFile->team_id)
                ->where('phone_normalized', $newContact['phone_normalized'])
                ->get()
                ->fetchOne();

            if (!empty($number)) {
                $diff = array_diff_assoc($newContact, $number);
                $isNew = !empty($diff);
                $newContact['id'] = $number['id'];

                if (!empty($number['is_deleted'])) {
                    $newContact['is_deleted'] = -1;
                }
            }
        }

        if (!empty($tags)) {
            $contactTags = $number
                ? ContactTagView::where('team_id', $this->dataFile->team_id)
                    ->where('contact_id', $newContact['id'])
                    ->getRows()
                : [];
            $contactTags = array_column($contactTags, 'tag');

            $tags = array_diff($tags, $contactTags);
            if (!empty($tags)) {
                $tags = array_map(function ($tag) use ($newContact) {
                    return [
                        'team_id' => $this->dataFile->team_id,
                        'contact_id' => $newContact['contact_id'],
                        'tag' => $tag,
                        'date_created' => date('Y-m-d H:i:s'),
                    ];
                }, $tags);

                ContactTagView::insertAssoc($tags);
            }
        }

        if (!$isNew) {
            return [];
        }

        return $newContact;
    }

    public function filterRow(array $row): bool
    {
        return !empty($row['email_normalized'])
            || (!empty($row['phone_normalized']) && !empty($row['country_id']));
    }

    private function getFirstRows($num = 15): array
    {
        $filePath = $this->csvFilePath();

        if (!file_exists($filePath)) {
            return [];
        }

        $file = new \SplFileObject($filePath);
        $file->seek(PHP_INT_MAX);
        $total = $file->key();
        $size = min($num, $total);
        $firstRows = [];

        for ($i = 0; $i < $size; $i++) {
            $file->seek($i);
            $firstRows[] = $file->current();
        }

        $file = null;

        return $firstRows;
    }

    private function autoDetectDelimiter(): void
    {
        $filePath = $this->storagePath . '/' . $this->dataFile->file_name;
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);

        // delimiter auto detect only for csv files
        if (in_array($ext, ['xls', 'xlsx'])) {
            return;
        }

        $delimiters = [',', ';', "\t", '|'];

        $firstRows = $this->getFirstRows();

        $delimitersCount = array_map(function ($delimiter) use ($firstRows) {
            return count(array_filter($firstRows, function ($row) use ($delimiter) {
                return substr_count($row, $delimiter) > 0;
            }));
        }, $delimiters);

        $delimiterIdx = array_keys($delimitersCount, max($delimitersCount))[0];
        $this->delimiter = $delimiters[$delimiterIdx];

        $this->log('Delimiter auto detected', [
            'delimiter' => $this->delimiter,
        ]);
    }

    private function csvFilePath(): string
    {
        $filePath = $this->storagePath . '/' . $this->dataFile->file_name;
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);

        if (in_array($ext, ['xls', 'xlsx']) && !empty($this->dataFile['meta']['csv_file_name'])) {
            return $this->storagePath . '/' . $this->dataFile['meta']['csv_file_name'];
        }

        $fileName = match ($ext) {
            'xls', 'xlsx' => $this->xls2csv(),
            default => $this->dataFile->file_name,
        };

        return $this->storagePath . '/' . $fileName;
    }

    protected function setDataFileStatus(DataFileStatusEnum $status): void
    {
        $this->dataFile->status_id = $status->value;
        $this->dataFile->saveOrFail();
    }

    protected function log(string $message, array $context = [], string $level = 'info'): void
    {
        Log::$level($message,
            array_merge([
                'data_file_id' => $this->dataFile->id,
            ], $context)
        );
    }

    private function getNumber(string $rawValue, int $countryId): ?string
    {
        $normalized = null;

        if ($phoneNumber = NumberService::normalize($rawValue, $countryId)) {
            $normalized = $phoneNumber->getCountryCode() . $phoneNumber->getNationalNumber();

            // <<<hack from old code
            if ($phoneNumber->getCountryCode() == 46) {
                if (strlen($normalized) > 9) {
                    $normalized = preg_replace('/^4646/', '46', $normalized);
                }
            }

            if ($phoneNumber->getCountryCode() == '49') {
                $normalized = preg_replace('/^4949/', '49', $normalized);
            }
            // end hack>>>
        }

        return $normalized;
    }

    private function getEmail(string $rawValue): ?string
    {
        if (empty($rawValue)) {
            return null;
        }

        return mb_strtolower(trim($rawValue));
    }

    private function getCountryId(?string $rawValue): ?int
    {
        if (empty($rawValue)) {
            return null;
        }

        if (!empty($this->dataFile->meta['fixed_country_id'])) {
            return $this->dataFile->meta['fixed_country_id'];
        }

        if (isset($this->countriesCache[$rawValue])) {
            return $this->countriesCache[$rawValue];
        }

        $countryId = null;

        try {
            $countryId = (int)CountryService::guessCountry($rawValue);
        } catch (\Exception) {
        }

        if (empty($countryId)) {
            return null;
        }

        $this->countriesCache[$rawValue] = $countryId;

        return $countryId;
    }

    private function getCustomStrArray($row, $columns): array
    {
        $data = [];

        for ($i = 1; $i <= 5; $i++) {
            $data["custom{$i}_str"] = $row[$columns["custom{$i}_str"] ?? -1] ?? null;
        }

        return array_filter($data, fn($value) => $value !== null);
    }

    private function getCustomIntArray($row, $columns): array
    {
        $data = [];

        for ($i = 1; $i <= 5; $i++) {
            $data["custom{$i}_int"] = $row[$columns["custom{$i}_int"] ?? -1] ?? null;
        }

        $data = array_filter($data, fn($value) => $value !== null);

        return array_map(fn($value) => (int)$value, $data);
    }

    private function getCustomDecArray($row, $columns): array
    {
        $data = [];

        for ($i = 1; $i <= 2; $i++) {
            $data["custom{$i}_dec"] = $row[$columns["custom{$i}_dec"] ?? -1] ?? null;
        }

        $data = array_filter($data, fn($value) => $value !== null);

        return array_map(fn($value) => (float)$value, $data);
    }

    private function getCustomDatetimeArray($row, $columns): array
    {
        $data = [];

        for ($i = 1; $i <= 5; $i++) {
            $data["custom{$i}_datetime"] = $row[$columns["custom{$i}_datetime"] ?? -1] ?? null;
        }

        $data = array_map(function ($value) {
            if ($value) {
                try {
                    return Carbon::parse($value)->toDateTimeString();
                } catch (\Exception) {
                    return null;
                }
            }

            return null;
        }, $data);

        return array_filter($data, fn($value) => $value !== null && $value != '1970-01-01 00:00:00');
    }
}
