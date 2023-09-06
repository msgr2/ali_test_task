<?php

namespace App\Models\Clickhouse;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use PhpClickHouseLaravel\BaseModel;

class Contact extends BaseModel implements Arrayable
{
    use HasFactory;

    public function newCollection(array $models = []): Collection
    {
        return new Collection($models);
    }

    public function toArray(): array
    {
        return $this->attributes;
    }
}
