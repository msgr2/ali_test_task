<?php

namespace App\Models\Clickhouse;

use PhpClickHouseLaravel\BaseModel;
use PhpClickHouseLaravel\Builder;
use PhpClickHouseLaravel\RawColumn;

class ContactTag extends BaseModel
{
    protected $table = 'contact_tags';
}
