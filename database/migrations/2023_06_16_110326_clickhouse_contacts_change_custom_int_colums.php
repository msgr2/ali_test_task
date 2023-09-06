<?php

return new class extends \PhpClickHouseLaravel\Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        static::write('alter table msgr.contacts modify column custom1_int Nullable(Int32)');
        static::write('alter table msgr.contacts modify column custom2_int Nullable(Int32)');
        static::write('alter table msgr.contacts modify column custom3_int Nullable(Int32)');
        static::write('alter table msgr.contacts modify column custom4_int Nullable(Int32)');
        static::write('alter table msgr.contacts modify column custom5_int Nullable(Int32)');

        static::write('alter table msgr.contacts_sms_materialized modify column custom1_int SimpleAggregateFunction(anyLast, Nullable(Int32))');
        static::write('alter table msgr.contacts_sms_materialized modify column custom2_int SimpleAggregateFunction(anyLast, Nullable(Int32))');
        static::write('alter table msgr.contacts_sms_materialized modify column custom3_int SimpleAggregateFunction(anyLast, Nullable(Int32))');
        static::write('alter table msgr.contacts_sms_materialized modify column custom4_int SimpleAggregateFunction(anyLast, Nullable(Int32))');
        static::write('alter table msgr.contacts_sms_materialized modify column custom5_int SimpleAggregateFunction(anyLast, Nullable(Int32))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
