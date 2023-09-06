<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self clicked_count()
 * @method static self sent_count()
 * @method static self country_id()
 * @method static self state_id()
 * //method static self network_id()
 * @method static self network_brand()
 * @method static self date_created()
 * @method static self last_sent()
 * @method static self last_clicked()
 * @method static self leads_count()
 * @method static self sales_count()
 * @method static self custom1_str()
 * @method static self custom2_str()
 * @method static self custom3_str()
 * @method static self custom4_str()
 * @method static self custom5_str()
 * @method static self custom1_dec()
 * @method static self custom2_dec()
 * @method static self custom1_datetime()
 * @method static self custom2_datetime()
 * @method static self custom3_datetime()
 * @method static self custom4_datetime()
 * @method static self custom5_datetime()
 * @method static self tags()
 */
class JqBuilderFieldEnum extends Enum
{
    public function toJqRule(array $customFields = []): array
    {
        return match ($this->label) {
            self::clicked_count()->label => [
                'field' => $this->label,
                'label' => 'Clicked Count',
                'type' => JqBuilderTypeEnum::integer(),
                'operators' => [
                    JqBuilderOperatorEnum::equal()->label,
                    JqBuilderOperatorEnum::not_equal()->label,
                    JqBuilderOperatorEnum::less()->label,
                    JqBuilderOperatorEnum::less_or_equal()->label,
                    JqBuilderOperatorEnum::greater()->label,
                    JqBuilderOperatorEnum::greater_or_equal()->label,
                ],
            ],
            self::sent_count()->label => [
                'field' => $this->label,
                'label' => 'Sent Count',
                'type' => JqBuilderTypeEnum::integer(),
                'operators' => [
                    JqBuilderOperatorEnum::equal()->label,
                    JqBuilderOperatorEnum::not_equal()->label,
                    JqBuilderOperatorEnum::less()->label,
                    JqBuilderOperatorEnum::less_or_equal()->label,
                    JqBuilderOperatorEnum::greater()->label,
                    JqBuilderOperatorEnum::greater_or_equal()->label,
                ],
            ],
            self::country_id()->label => [
                'field' => $this->label,
                'label' => 'Country',
                'type' => JqBuilderTypeEnum::integer(),
                'operators' => [
                    JqBuilderOperatorEnum::equal()->label,
                    JqBuilderOperatorEnum::not_equal()->label,
                    JqBuilderOperatorEnum::in()->label,
                    JqBuilderOperatorEnum::not_in()->label,
                ],
            ],
            self::state_id()->label => [
                'field' => $this->label,
                'label' => 'State',
                'type' => JqBuilderTypeEnum::integer(),
                'operators' => [
                    JqBuilderOperatorEnum::equal()->label,
                    JqBuilderOperatorEnum::not_equal()->label,
                    JqBuilderOperatorEnum::in()->label,
                    JqBuilderOperatorEnum::not_in()->label,
                ],
            ],
//            self::network_id()->label => [
//                'field' => $this->label,
//                'label' => 'Network',
//            ],
            self::network_brand()->label => [
                'field' => $this->label,
                'label' => 'Network Brand',
                'type' => JqBuilderTypeEnum::string(),
                'operators' => [
                    JqBuilderOperatorEnum::contains()->label,
                    JqBuilderOperatorEnum::not_contains()->label,
                    JqBuilderOperatorEnum::begins_with()->label,
                    JqBuilderOperatorEnum::not_ends_with()->label,
                    JqBuilderOperatorEnum::ends_with()->label,
                    JqBuilderOperatorEnum::not_ends_with()->label,
                    JqBuilderOperatorEnum::is_empty()->label,
                    JqBuilderOperatorEnum::is_not_empty()->label,
                ],
            ],
            self::date_created()->label => [
                'field' => $this->label,
                'label' => 'Date Created',
                'type' => JqBuilderTypeEnum::date(),
                'operators' => [
                    JqBuilderOperatorEnum::equal()->label,
                    JqBuilderOperatorEnum::not_equal()->label,
                    JqBuilderOperatorEnum::less()->label,
                    JqBuilderOperatorEnum::less_or_equal()->label,
                    JqBuilderOperatorEnum::greater()->label,
                    JqBuilderOperatorEnum::greater_or_equal()->label,
                ],
            ],
            self::last_sent()->label => [
                'field' => $this->label,
                'label' => 'Last Sent',
                'type' => JqBuilderTypeEnum::date(),
                'operators' => [
                    JqBuilderOperatorEnum::equal()->label,
                    JqBuilderOperatorEnum::not_equal()->label,
                    JqBuilderOperatorEnum::less()->label,
                    JqBuilderOperatorEnum::less_or_equal()->label,
                    JqBuilderOperatorEnum::greater()->label,
                    JqBuilderOperatorEnum::greater_or_equal()->label,
                ],
            ],
            self::last_clicked()->label => [
                'field' => $this->label,
                'label' => 'Last Clicked',
                'type' => JqBuilderTypeEnum::date(),
                'operators' => [
                    JqBuilderOperatorEnum::equal()->label,
                    JqBuilderOperatorEnum::not_equal()->label,
                    JqBuilderOperatorEnum::less()->label,
                    JqBuilderOperatorEnum::less_or_equal()->label,
                    JqBuilderOperatorEnum::greater()->label,
                    JqBuilderOperatorEnum::greater_or_equal()->label,
                ],
            ],
            self::leads_count()->label => [
                'field' => $this->label,
                'label' => 'Leads Count',
                'type' => JqBuilderTypeEnum::integer(),
                'operators' => [
                    JqBuilderOperatorEnum::equal()->label,
                    JqBuilderOperatorEnum::not_equal()->label,
                    JqBuilderOperatorEnum::less()->label,
                    JqBuilderOperatorEnum::less_or_equal()->label,
                    JqBuilderOperatorEnum::greater()->label,
                    JqBuilderOperatorEnum::greater_or_equal()->label,
                ],
            ],
            self::sales_count()->label => [
                'field' => $this->label,
                'label' => 'Sales Count',
                'type' => JqBuilderTypeEnum::integer(),
                'operators' => [
                    JqBuilderOperatorEnum::equal()->label,
                    JqBuilderOperatorEnum::not_equal()->label,
                    JqBuilderOperatorEnum::less()->label,
                    JqBuilderOperatorEnum::less_or_equal()->label,
                    JqBuilderOperatorEnum::greater()->label,
                    JqBuilderOperatorEnum::greater_or_equal()->label,
                ],
            ],
            self::custom1_str()->label => [
                'field' => $this->label,
                'label' => $customFields[$this->label] ?? 'Custom 1 String',
                'type' => JqBuilderTypeEnum::string(),
                'operators' => [
                    JqBuilderOperatorEnum::contains()->label,
                    JqBuilderOperatorEnum::not_contains()->label,
                    JqBuilderOperatorEnum::begins_with()->label,
                    JqBuilderOperatorEnum::not_ends_with()->label,
                    JqBuilderOperatorEnum::ends_with()->label,
                    JqBuilderOperatorEnum::not_ends_with()->label,
                    JqBuilderOperatorEnum::is_empty()->label,
                    JqBuilderOperatorEnum::is_not_empty()->label,
                ],
            ],
            self::custom2_str()->label => [
                'field' => $this->label,
                'label' => $customFields[$this->label] ?? 'Custom 2 String',
                'type' => JqBuilderTypeEnum::string(),
                'operators' => [
                    JqBuilderOperatorEnum::contains()->label,
                    JqBuilderOperatorEnum::not_contains()->label,
                    JqBuilderOperatorEnum::begins_with()->label,
                    JqBuilderOperatorEnum::not_ends_with()->label,
                    JqBuilderOperatorEnum::ends_with()->label,
                    JqBuilderOperatorEnum::not_ends_with()->label,
                    JqBuilderOperatorEnum::is_empty()->label,
                    JqBuilderOperatorEnum::is_not_empty()->label,
                ],
            ],
            self::custom3_str()->label => [
                'field' => $this->label,
                'label' => $customFields[$this->label] ?? 'Custom 3 String',
                'type' => JqBuilderTypeEnum::string(),
                'operators' => [
                    JqBuilderOperatorEnum::contains()->label,
                    JqBuilderOperatorEnum::not_contains()->label,
                    JqBuilderOperatorEnum::begins_with()->label,
                    JqBuilderOperatorEnum::not_ends_with()->label,
                    JqBuilderOperatorEnum::ends_with()->label,
                    JqBuilderOperatorEnum::not_ends_with()->label,
                    JqBuilderOperatorEnum::is_empty()->label,
                    JqBuilderOperatorEnum::is_not_empty()->label,
                ],
            ],
            self::custom4_str()->label => [
                'field' => $this->label,
                'label' => $customFields[$this->label] ?? 'Custom 4 String',
                'type' => JqBuilderTypeEnum::string(),
                'operators' => [
                    JqBuilderOperatorEnum::contains()->label,
                    JqBuilderOperatorEnum::not_contains()->label,
                    JqBuilderOperatorEnum::begins_with()->label,
                    JqBuilderOperatorEnum::not_ends_with()->label,
                    JqBuilderOperatorEnum::ends_with()->label,
                    JqBuilderOperatorEnum::not_ends_with()->label,
                    JqBuilderOperatorEnum::is_empty()->label,
                    JqBuilderOperatorEnum::is_not_empty()->label,
                ],
            ],
            self::custom5_str()->label => [
                'field' => $this->label,
                'label' => $customFields[$this->label] ?? 'Custom 5 String',
                'type' => JqBuilderTypeEnum::string(),
                'operators' => [
                    JqBuilderOperatorEnum::contains()->label,
                    JqBuilderOperatorEnum::not_contains()->label,
                    JqBuilderOperatorEnum::begins_with()->label,
                    JqBuilderOperatorEnum::not_ends_with()->label,
                    JqBuilderOperatorEnum::ends_with()->label,
                    JqBuilderOperatorEnum::not_ends_with()->label,
                    JqBuilderOperatorEnum::is_empty()->label,
                    JqBuilderOperatorEnum::is_not_empty()->label,
                ],
            ],
            self::custom1_dec()->label => [
                'field' => $this->label,
                'label' => $customFields[$this->label] ?? 'Custom 1 Decimal',
                'type' => JqBuilderTypeEnum::double(),
                'operators' => [
                    JqBuilderOperatorEnum::equal()->label,
                    JqBuilderOperatorEnum::not_equal()->label,
                    JqBuilderOperatorEnum::less()->label,
                    JqBuilderOperatorEnum::less_or_equal()->label,
                    JqBuilderOperatorEnum::greater()->label,
                    JqBuilderOperatorEnum::greater_or_equal()->label,
                ],
            ],
            self::custom2_dec()->label => [
                'field' => $this->label,
                'label' => $customFields[$this->label] ?? 'Custom 2 Decimal',
                'type' => JqBuilderTypeEnum::double(),
                'operators' => [
                    JqBuilderOperatorEnum::equal()->label,
                    JqBuilderOperatorEnum::not_equal()->label,
                    JqBuilderOperatorEnum::less()->label,
                    JqBuilderOperatorEnum::less_or_equal()->label,
                    JqBuilderOperatorEnum::greater()->label,
                    JqBuilderOperatorEnum::greater_or_equal()->label,
                ],
            ],
            self::custom1_datetime()->label => [
                'field' => $this->label,
                'label' => $customFields[$this->label] ?? 'Custom 1 Datetime',
                'type' => JqBuilderTypeEnum::datetime(),
                'operators' => [
                    JqBuilderOperatorEnum::equal()->label,
                    JqBuilderOperatorEnum::not_equal()->label,
                    JqBuilderOperatorEnum::less()->label,
                    JqBuilderOperatorEnum::less_or_equal()->label,
                    JqBuilderOperatorEnum::greater()->label,
                    JqBuilderOperatorEnum::greater_or_equal()->label,
                ],
            ],
            self::custom2_datetime()->label => [
                'field' => $this->label,
                'label' => $customFields[$this->label] ?? 'Custom 2 Datetime',
                'type' => JqBuilderTypeEnum::datetime(),
                'operators' => [
                    JqBuilderOperatorEnum::equal()->label,
                    JqBuilderOperatorEnum::not_equal()->label,
                    JqBuilderOperatorEnum::less()->label,
                    JqBuilderOperatorEnum::less_or_equal()->label,
                    JqBuilderOperatorEnum::greater()->label,
                    JqBuilderOperatorEnum::greater_or_equal()->label,
                ],
            ],
            self::custom3_datetime()->label => [
                'field' => $this->label,
                'label' => $customFields[$this->label] ?? 'Custom 3 Datetime',
                'type' => JqBuilderTypeEnum::datetime(),
                'operators' => [
                    JqBuilderOperatorEnum::equal()->label,
                    JqBuilderOperatorEnum::not_equal()->label,
                    JqBuilderOperatorEnum::less()->label,
                    JqBuilderOperatorEnum::less_or_equal()->label,
                    JqBuilderOperatorEnum::greater()->label,
                    JqBuilderOperatorEnum::greater_or_equal()->label,
                ],
            ],
            self::custom4_datetime()->label => [
                'field' => $this->label,
                'label' => $customFields[$this->label] ?? 'Custom 4 Datetime',
                'type' => JqBuilderTypeEnum::datetime(),
                'operators' => [
                    JqBuilderOperatorEnum::equal()->label,
                    JqBuilderOperatorEnum::not_equal()->label,
                    JqBuilderOperatorEnum::less()->label,
                    JqBuilderOperatorEnum::less_or_equal()->label,
                    JqBuilderOperatorEnum::greater()->label,
                    JqBuilderOperatorEnum::greater_or_equal()->label,
                ],
            ],
            self::custom5_datetime()->label => [
                'field' => $this->label,
                'label' => $customFields[$this->label] ?? 'Custom 5 Datetime',
                'type' => JqBuilderTypeEnum::datetime(),
                'operators' => [
                    JqBuilderOperatorEnum::equal()->label,
                    JqBuilderOperatorEnum::not_equal()->label,
                    JqBuilderOperatorEnum::less()->label,
                    JqBuilderOperatorEnum::less_or_equal()->label,
                    JqBuilderOperatorEnum::greater()->label,
                    JqBuilderOperatorEnum::greater_or_equal()->label,
                ],
            ],
            self::tags()->label => [
                'field' => $this->label,
                'label' => 'Tag',
                'type' => JqBuilderTypeEnum::string(),
                'operators' => [
                    JqBuilderOperatorEnum::contains()->label,
                    JqBuilderOperatorEnum::not_contains()->label,
                    JqBuilderOperatorEnum::begins_with()->label,
                    JqBuilderOperatorEnum::not_ends_with()->label,
                    JqBuilderOperatorEnum::ends_with()->label,
                    JqBuilderOperatorEnum::not_ends_with()->label,
                    JqBuilderOperatorEnum::in()->label,
                    JqBuilderOperatorEnum::not_in()->label,
                ],
            ],
        };
    }
}
