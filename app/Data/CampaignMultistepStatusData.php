<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class CampaignMultistepStatusData extends Data
{

    public function __construct(
        public ?int    $current_step = 0,
        public ?int    $total_available_contacts = 0,
        public ?int    $total_sent = 0,
        public ?int    $last_sent_timestamp = null,
        public ?float  $start_timestamp = null,
        public ?array  $initial_brands = [],
        public ?array  $steps_performance = [],
        public ?string $status = 'in_progress',
    )
    {
    }
}
