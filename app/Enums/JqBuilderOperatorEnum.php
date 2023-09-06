<?php

namespace App\Enums;

use App\Models\Clickhouse\Views\ContactTagView;
use App\Models\Segment;
use Illuminate\Support\Arr;
use Spatie\Enum\Enum;

/**
 * @method static self equal()
 * @method static self not_equal()
 * @method static self in()
 * @method static self not_in()
 * @method static self less()
 * @method static self less_or_equal()
 * @method static self greater()
 * @method static self greater_or_equal()
 * @method static self begins_with()
 * @method static self not_begins_with()
 * @method static self contains()
 * @method static self not_contains()
 * @method static self ends_with()
 * @method static self not_ends_with()
 * @method static self is_empty()
 * @method static self is_not_empty()
 * //method static self is_null()
 * //method static self is_not_null()
 */
class JqBuilderOperatorEnum extends Enum
{
    protected static function values(): array
    {
        return [
            'equal' => 'equals',
            'not_equal' => 'notEquals',
            'in' => 'in',
            'not_in' => 'not in',
            'less' => 'less',
            'less_or_equal' => 'lessOrEquals',
            'greater' => 'greater',
            'greater_or_equal' => 'greaterOrEquals',
            'begins_with' => 'startsWith',
            'not_begins_with' => 'not startsWith',
            'contains' => 'like',
            'not_contains' => 'not like',
            'ends_with' => 'endsWith',
            'not_ends_with' => 'not endsWith',
            'is_empty' => 'empty',
            'is_not_empty' => 'notEmpty',
            'is_null' => 'isNull',
            'is_not_null' => 'isNotNull',
        ];
    }

    public function toSql(Segment $segment, JqBuilderFieldEnum $field, array|string $binds): string
    {
        $fieldName = $field->value;

        if (is_array($binds)) {
            $bindKey = implode(', ', Arr::map($binds, fn($key) => ":$key"));
        } else {
            $bindKey = ":$binds";
        }

        if ($field->equals(JqBuilderFieldEnum::tags())) {
            $whereRaw = match ($this->value) {
                self::begins_with()->value,
                self::not_begins_with()->value,
                self::ends_with()->value,
                self::not_ends_with()->value => "$this->value(tag, $bindKey)",

                self::in()->value,
                self::not_in()->value => "tag $this->value ($bindKey)",

                self::contains()->value,
                self::not_contains()->value => "tag $this->value $bindKey",
            };
            $sub = ContactTagView::select('contact_id')
                ->where('team_id', $segment->team_id)
                ->where('is_deleted', 0)
                ->whereRaw($whereRaw)
                ->toSql();
            return "contact_id in ($sub)";
        }

        if ($field->equals(
            JqBuilderFieldEnum::date_created(),
            JqBuilderFieldEnum::last_sent(),
            JqBuilderFieldEnum::last_clicked(),
        )) {
            $fieldName = "toDate($fieldName)";
            $bindKey = "parseDateTime32BestEffort($bindKey)";
        } else if ($field->equals(
            JqBuilderFieldEnum::custom1_datetime(),
            JqBuilderFieldEnum::custom2_datetime(),
            JqBuilderFieldEnum::custom3_datetime(),
            JqBuilderFieldEnum::custom4_datetime(),
            JqBuilderFieldEnum::custom5_datetime(),
        )) {
            $fieldName = "toDateTime($fieldName)";
            $bindKey = "parseDateTime32BestEffort($bindKey)";
        }

        return match ($this->value) {
            self::equal()->value,
            self::not_equal()->value,
            self::less()->value,
            self::less_or_equal()->value,
            self::greater()->value,
            self::greater_or_equal()->value,
            self::begins_with()->value,
            self::not_begins_with()->value,
            self::ends_with()->value,
            self::not_ends_with()->value => "$this->value($fieldName, $bindKey)",

            self::contains()->value,
            self::not_contains()->value => "$fieldName $this->value $bindKey",

            self::in()->value,
            self::not_in()->value => "$fieldName $this->value ($bindKey)",

            self::is_empty()->value => "($this->value($fieldName) or isNull($fieldName))",
            self::is_not_empty()->value => "($this->value($fieldName) and isNotNull($fieldName))",
        };
    }
}
