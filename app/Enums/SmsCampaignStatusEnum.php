<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self draft() - still in editing
 * @method static self pending() - waiting to be sent
 * @method static self in_progress() - in progress
 * @method static self sent()
 * @method static self failed()
 * @method static self cancelled()
 */
class SmsCampaignStatusEnum extends Enum
{
}
