<?php

namespace App\Services;

use App\Enums\JqBuilderFieldEnum;
use App\Enums\JqBuilderOperatorEnum;
use App\Enums\SegmentTypeEnum;
use App\Models\Clickhouse\Views\ContactSmsView;
use App\Models\Clickhouse\Views\ContactTagView;
use App\Models\Segment;
use ClickHouseDB\Query\Degeneration\Bindings;
use ClickHouseDB\Query\Query;
use Illuminate\Support\Facades\Log;
use PhpClickHouseLaravel\Builder;
use PhpClickHouseLaravel\RawColumn;

class SegmentBuilderService
{
    public static function create(Segment $segment): ?Builder
    {
        if (empty($segment->meta['query'])) {
            Log::warning('Empty segment query', [
                'segment' => $segment->toArray(),
            ]);
            return null;
        }

        $res = self::parse($segment);

        if (empty($res) || empty($res['sql'])) {
            Log::warning("Can't parse segment query", [
                'segment' => $segment->toArray(),
            ]);
            return null;
        }

        $bindings = new Bindings();
        foreach ($res['binds'] as $col => $value) {
            $bindings->bindParam($col, $value);
        }

        $builder = match ($segment->type) {
            SegmentTypeEnum::numbers()->value => ContactSmsView::where('team_id', $segment->team_id),
            // todo
//            SegmentTypeEnum::emails()->value => ContactSmsView::where('team_id', $segment->team_id),
        };
        $builder->where('is_deleted', 0);
        $builder->whereRaw(new Query($res['sql'], [$bindings]));

        return $builder;
    }

    public static function parse(Segment $segment, ?array $conditions = null, &$ruleIdx = 1): array
    {
        $data = [];

        if (empty($conditions)) {
            $conditions = $segment->meta['query'];
        }

        if (self::isGroup($conditions)) {
            $arr = [
                'condition' => $conditions['condition'],
                'rules' => self::parse($segment, $conditions['rules'], $ruleIdx),
            ];
            $arr['sql'] = self::getGroupSql($arr);
            $arr['binds'] = self::getGroupBinds($arr);
            return $arr;
        }

        foreach ($conditions as $rule) {
            if (self::isGroup($rule)) {
                $data[] = self::parse($segment, $rule, $ruleIdx);
                continue;
            }

            if (self::isRule($rule)) {
                $data[] = self::parseRule($segment, $rule, $ruleIdx);
                $ruleIdx += 1;
                continue;
            }

            Log::warning('Unknown rule', [
                'rule' => $rule,
            ]);
        }

        return $data;
    }

    public static function isGroup($value): bool
    {
        return is_array($value) && array_key_exists('condition', $value);
    }

    private static function getGroupSql($group): string
    {
        $rules = [];
        foreach ($group['rules'] as $rule) {
            if (self::isGroup($rule)) {
                $rules[] = self::getGroupSql($rule);
                continue;
            }

            $rules[] = $rule['sql'];
        }

        return '(' . implode(' ' . $group['condition'] . ' ', $rules) . ')';
    }

    private static function getGroupBinds($group): array
    {
        $binds = [];

        foreach ($group['rules'] as $rule) {
            if (self::isGroup($rule)) {
                $binds = array_merge($binds, self::getGroupBinds($rule));
                continue;
            }

            if (is_array($rule['bind_key'])) {
                foreach ($rule['bind_key'] as $k => $v) {
                    $binds[$v] = $rule['value'][$k];
                }
            } else {
                $binds[$rule['bind_key']] = $rule['value'];
            }
        }

        return $binds;
    }

    public static function isRule($value): bool
    {
        return is_array($value)
            && array_key_exists('operator', $value)
            && array_key_exists('field', $value)
            && array_key_exists('value', $value);
    }

    private static function parseRule(Segment $segment, array $rule, int &$ruleIdx): ?array
    {
        $op = JqBuilderOperatorEnum::tryFrom($rule['operator']);
        $field = JqBuilderFieldEnum::tryFrom($rule['field']);

        if (empty($op) || empty($field)) {
            Log::warning('Unknown rule operator or rule field', [
                'rule' => $rule,
            ]);
            return null;
        }

        $bindKey = 'rule_' . $ruleIdx;
        $value = $rule['value'];

        if (is_array($value)) {
            $bindKey = [];

            foreach ($value as $k => $v) {
                $bindKey[] = 'rule_' . $ruleIdx;
                $ruleIdx += 1;
            }
        }

        $sql = $op->toSql($segment, $field, $bindKey);

        return [
            'field' => $field,
            'operator' => $op->value,
            'value' => $value,
            'sql' => $sql,
            'bind_key' => $bindKey,
        ];
    }

    public static function getWhereFromSegment(Segment $segment)
    {
        $res = self::parse($segment);

        if (empty($res) || empty($res['sql'])) {
            Log::warning("Can't parse segment query", [
                'segment' => $segment->toArray(),
            ]);
            return null;
        }

        $bindings = new Bindings();
        foreach ($res['binds'] as $col => $value) {
            $bindings->bindParam($col, $value);
        }

        return new Query($res['sql'], [$bindings]);
    }
}
