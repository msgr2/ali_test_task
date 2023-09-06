<?php

namespace App\Models\Clickhouse\Views;

use PhpClickHouseLaravel\BaseModel;
use PhpClickHouseLaravel\Builder;
use PhpClickHouseLaravel\RawColumn;

class ContactTagView extends BaseModel
{
    protected $table = 'contact_tags_view';
    protected $tableForInserts = 'contact_tags';
}
