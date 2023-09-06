<?php


use PhpClickHouseLaravel\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        static::write('drop view if exists contacts_sms_mv');
        static::write('drop view if exists contacts_sms_view');

        static::write('alter table contacts rename column if exists id to contact_id');
        static::write('alter table contacts_sms_materialized rename column if exists id to contact_id');

        static::write("CREATE MATERIALIZED VIEW msgr.contacts_sms_mv TO msgr.contacts_sms_materialized
(
    `team_id` UUID,
    `phone_normalized` UInt64,
    `contact_id` Nullable(UUID),
    `foreign_id` Nullable(String),
    `phone_is_good` Nullable(UInt8),
    `phone_is_good_reason` Nullable(UInt8),
    `name` Nullable(String),
    `country_id` Nullable(UInt32),
    `state_id` Nullable(UInt32),
    `state_id_reason` Nullable(UInt8),
    `custom1_str` Nullable(String),
    `custom2_str` Nullable(String),
    `custom3_str` Nullable(String),
    `custom4_str` Nullable(String),
    `custom5_str` Nullable(String),
    `custom1_int` Nullable(Int32),
    `custom2_int` Nullable(Int32),
    `custom3_int` Nullable(Int32),
    `custom4_int` Nullable(Int32),
    `custom5_int` Nullable(Int32),
    `custom1_dec` Nullable(Decimal(18, 15)),
    `custom2_dec` Nullable(Decimal(18, 15)),
    `custom1_datetime` Nullable(DateTime),
    `custom2_datetime` Nullable(DateTime),
    `custom3_datetime` Nullable(DateTime),
    `custom4_datetime` Nullable(DateTime),
    `custom5_datetime` Nullable(DateTime),
    `date_created` Nullable(DateTime),
    `date_updated` Nullable(DateTime),
    `meta` Nullable(String),
    `is_deleted` Int64
) AS
SELECT
    team_id,
    phone_normalized,
    contact_id,
    foreign_id,
    phone_is_good,
    phone_is_good_reason,
    name,
    country_id,
    state_id,
    state_id_reason,
    custom1_str,
    custom2_str,
    custom3_str,
    custom4_str,
    custom5_str,
    custom1_int,
    custom2_int,
    custom3_int,
    custom4_int,
    custom5_int,
    custom1_dec,
    custom2_dec,
    custom1_datetime,
    custom2_datetime,
    custom3_datetime,
    custom4_datetime,
    custom5_datetime,
    date_created,
    date_updated,
    meta,
    is_deleted
FROM msgr.contacts
WHERE phone_normalized > 0");

        static::write("CREATE VIEW msgr.contacts_sms_view AS
SELECT
  `team_id`,
  `phone_normalized`,
  anyLast(`contact_id`) AS `contact_id`,
  anyLast(`foreign_id`) AS `foreign_id`,
  anyLast(`last_sent`) AS `last_sent`,
  anyLast(`last_clicked`) AS `last_clicked`,
  sum(`sent_count`) AS `sent_count`,
  sum(`clicked_count`) AS `clicked_count`,
  sum(`leads_count`) AS `leads_count`,
  sum(`sales_count`) AS `sales_count`,
  sum(`profit_sum`) AS `profit_sum`,
  anyLast(`network_brand`) AS `network_brand`,
  anyLast(`network_id`) AS `network_id`,
  anyLast(`network_reason`) AS `network_reason`,
  anyLast(`phone_is_good`) AS `phone_is_good`,
  anyLast(`phone_is_good_reason`) AS `phone_is_good_reason`,
  anyLast(`name`) AS `name`,
  anyLast(`country_id`) AS `country_id`,
  anyLast(`state_id`) AS `state_id`,
  anyLast(`state_id_reason`) AS `state_id_reason`,
  anyLast(`custom1_str`) AS `custom1_str`,
  anyLast(`custom2_str`) AS `custom2_str`,
  anyLast(`custom3_str`) AS `custom3_str`,
  anyLast(`custom4_str`) AS `custom4_str`,
  anyLast(`custom5_str`) AS `custom5_str`,
  anyLast(`custom1_int`) AS `custom1_int`,
  anyLast(`custom2_int`) AS `custom2_int`,
  anyLast(`custom3_int`) AS `custom3_int`,
  anyLast(`custom4_int`) AS `custom4_int`,
  anyLast(`custom5_int`) AS `custom5_int`,
  anyLast(`custom1_dec`) AS `custom1_dec`,
  anyLast(`custom2_dec`) AS `custom2_dec`,
  anyLast(`custom1_datetime`) AS `custom1_datetime`,
  anyLast(`custom2_datetime`) AS `custom2_datetime`,
  anyLast(`custom3_datetime`) AS `custom3_datetime`,
  anyLast(`custom4_datetime`) AS `custom4_datetime`,
  anyLast(`custom5_datetime`) AS `custom5_datetime`,
  anyLast(`meta`) AS `meta`,
  anyLast(`date_created`) AS `date_created`,
  anyLast(`date_updated`) AS `date_updated`,
  sum(`is_deleted`) AS `is_deleted`
FROM contacts_sms_materialized
GROUP BY `phone_normalized`, `team_id`;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
