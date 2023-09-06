<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class CampaignMultistepSettingsData extends Data
{
    public function __construct(
        //difference between optimisation steps
        public int   $step_size = 200,
        //for example - 3% ctr is 3
        public float $min_ctr = 3,
        public int   $step_delay = 5,
        public bool  $split_networks = true,
        public bool  $optimise_texts = true,
//        public bool $is_auto_expand_texts,
        public bool  $optimise_sender_ids = true,
        public bool  $optimise_segments = true,
        public bool  $optimise_routes = true,
        public bool  $optimise_countries = true,
        public bool  $optimise_carriers = true,
    )
    {
    }
}
