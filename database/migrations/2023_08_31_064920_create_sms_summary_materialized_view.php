<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::connection('clickhouse')->statement("
            CREATE MATERIALIZED VIEW sms_summary_materialized_view
            ENGINE = SummingMergeTree(date, (sms_routing_route_id, date), 8192)
            POPULATE
            AS
            SELECT
                toStartOfDay(date) as date,
                sms_routing_route_id,
                sum(sent_count) as sent_count,
                sum(cost) as cost,
                sum(clicks) as clicks,
                sum(leads) as leads,
                sum(sales) as sales
            FROM
                sms_sendlogs
            GROUP BY
                sms_routing_route_id, date
        ");
    }

    public function down()
    {
        DB::connection('clickhouse')->statement("DROP MATERIALIZED VIEW IF EXISTS sms_summary_materialized_view");
    }

};
