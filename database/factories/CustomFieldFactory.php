<?php

namespace Database\Factories;

use App\Enums\CustomFieldEnum;
use App\Models\CustomField;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomFieldFactory extends Factory
{
    protected $model = CustomField::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid,
            'team_id' => $this->faker->uuid,
            'field_name' => $this->faker->word,
            'field_key' => $this->faker->randomElement(CustomFieldEnum::toValues()),
        ];
    }
}
