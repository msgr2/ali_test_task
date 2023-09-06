<?php

namespace Database\Factories;

use App\Models\DataFile;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class DataFileFactory extends Factory
{
    protected $model = DataFile::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'file_name' => $this->faker->word . '.csv',
            'file_size' => $this->faker->randomNumber(),
            'meta' => [
                'columns' => [
                    'number' => 0,
                    'country' => 1,
                ],
            ],
            'created_at' => Carbon::now(),
        ];
    }
}
