<?php

namespace App\Models\Clickhouse\Views;

use PhpClickHouseLaravel\BaseModel;
use PhpClickHouseLaravel\Builder;
use PhpClickHouseLaravel\RawColumn;

class ContactSmsView extends BaseModel
{
    protected $table = 'contacts_sms_view';
    protected $tableForInserts = 'contacts';
}
