<?php

namespace Database\Factories;

use App\Enums\SegmentStatusEnum;
use App\Enums\SegmentTypeEnum;
use App\Models\Segment;
use App\Services\CountryService;
use Illuminate\Database\Eloquent\Factories\Factory;

class SegmentFactory extends Factory
{
    protected $model = Segment::class;

    public function definition(): array
    {
        return [
            'team_id' => $this->faker->uuid,
            'name' => $this->faker->name,
            'status_id' => SegmentStatusEnum::active()->value,
        ];
    }

    public function withNumbersSample1($country = 'uk'): static
    {
        $countryId = CountryService::guessCountry($country);
        return $this->state(function (array $attributes) use ($countryId) {
            return [
                'type' => SegmentTypeEnum::numbers()->value,
                'meta' => [
                    'query' => [
                        'condition' => 'AND',
                        'rules' => [
                            [
                                'field' => 'clicked_count',
                                'operator' => 'greater_or_equal',
                                'value' => 0,
                            ],
                            [
                                'field' => 'country_id',
                                'operator' => 'equal',
                                'value' => $countryId,
                            ],
                            [
                                'field' => 'date_created',
                                'operator' => 'equal',
                                'value' => now()->toDateString(),
                            ],
                        ],
                    ],
                ],
            ];
        });
    }

    public function withNumbersSample2(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => SegmentTypeEnum::numbers()->value,
                'meta' => [
                    'query' => [
                        'condition' => 'AND',
                        'rules' => [
                            [
                                'field' => 'clicked_count',
                                'operator' => 'greater',
                                'value' => 0,
                            ],
                            [
                                'field' => 'country_id',
                                'operator' => 'equal',
                                'value' => 225,
                            ],
                            [
                                'condition' => 'OR',
                                'rules' => [
                                    [
                                        'field' => 'leads_count',
                                        'operator' => 'equal',
                                        'value' => 1,
                                    ],
                                    [
                                        'field' => 'sales_count',
                                        'operator' => 'equal',
                                        'value' => 1,
                                    ],
                                ],
                            ],
                            [
                                'field' => 'date_created',
                                'operator' => 'equal',
                                'value' => now()->toDateString(),
                            ],
                        ],
                    ],
                ],
            ];
        });
    }

    public function withNumbersSample3(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => SegmentTypeEnum::numbers()->value,
                'meta' => [
                    'query' => [
                        'condition' => 'AND',
                        'rules' => [
                            [
                                'field' => 'clicked_count',
                                'operator' => 'greater',
                                'value' => 0,
                            ],
                            [
                                'field' => 'tags',
                                'operator' => 'in',
                                'value' => [
                                    'user-tag-1',
                                    'user-tag-2',
                                ],
                            ],
                            [
                                'field' => 'tags',
                                'operator' => 'begins_with',
                                'value' => 'user',
                            ],
                        ],
                    ],
                ],
            ];
        });
    }

    public function withFilterNetwork(array $brandNames)
    {
        $rules = [];
        foreach ($brandNames as $brand) {
            $rules[] = [
                'id' => 'network_brand',
                'field' => 'network_brand',
                'type' => 'string',
                'input' => 'text',
                'operator' => 'contains',
                'value' => $brand,
            ];
        }
        return $this->state(function (array $attributes) use ($rules) {
            return [
                'type' => SegmentTypeEnum::numbers()->value,
                'meta' => [
                    'query' => [
                        'condition' => 'OR',
                        'rules' => $rules,
                    ],
                ],
            ];
        });

    }
}
