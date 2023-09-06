<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self numbers()
 * @method static self emails()
 */
class SegmentTypeEnum extends Enum
{
    protected static function values(): array
    {
        return [
            'numbers' => 1,
            'emails' => 2,
        ];
    }
}
