<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self inactive()
 * @method static self active()
 */
class SegmentStatusEnum extends Enum
{
    protected static function values(): array
    {
        return [
            'inactive' => 0,
            'active' => 1,
        ];
    }
}
