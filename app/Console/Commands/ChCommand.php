<?php

namespace App\Console\Commands;

use App\Services\ClickhouseService;
use ClickHouseDB\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class ChCommand extends Command
{
    protected $signature = 'ch:fresh';

    protected $description = 'Clickhouse drop all tables and migrate fresh';

    public function handle(): void
    {
        ClickhouseService::dropAllTables();
        Artisan::call('migrate:fresh');
    }
}
