<?php

namespace App\Services;

use ClickHouseDB\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClickhouseService
{
    public static function dropAllTables()
    {
        /** @var Client $db */
        $db = DB::connection('clickhouse')->getClient();
        $tables = $db->showTables();
        foreach ($tables as $table) {
            $db->write('DROP TABLE IF EXISTS `' . $table['name'] . '`');
        }
    }

    public static function getClient(): Client
    {
        return DB::connection('clickhouse')->getClient();
    }

    public static function batchInsertModelCollection(Collection $collection): void
    {
        if ($collection->isEmpty()) {
            return;
        }

        /** @var Model $model */
        $model = $collection->first();
        $columns = [];
//        foreach ($model->getAttributes() as $key => $val) {
//            $columns[] = $key;
//        }
        $insert = [];
        foreach ($collection as $modelCollection) {
//            /** @var Contact $modelCollection */
            $insert[] = $modelCollection->attributesToArray();
        }

        $db = self::getClient();
        $db->insertAssocBulk($model->getTable(), $insert, $columns);
    }

    public static function query(string $query, $treeValue = false)
    {
        $db = self::getClient();
        $res = $db->select($query);
        if ($treeValue) {
            return $res->rowsAsTree($treeValue);
        } else {
            return $res->rows();
        }
    }
}
