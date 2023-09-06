<?php

namespace App\Models\Clickhouse\Materialized;

use PhpClickHouseLaravel\BaseModel;

class ContactSmsMaterialized extends BaseModel
{
    protected $table = 'contacts_sms_materialized';
}
