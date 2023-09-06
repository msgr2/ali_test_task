<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Clickhouse\Contact
 */
class ContactSmsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'team_id' => $this->resource['team_id'],
            'phone_normalized' => $this->resource['phone_normalized'],
            'contact_id' => $this->resource['contact_id'],
            'foreign_id' => $this->resource['foreign_id'],
            'last_sent' => $this->resource['last_sent'],
            'last_clicked' => $this->resource['last_clicked'],
            'sent_count' => $this->resource['sent_count'],
            'clicked_count' => $this->resource['clicked_count'],
            'leads_count' => $this->resource['leads_count'],
            'sales_count' => $this->resource['sales_count'],
//            'profit_sum' => $this->resource['profit_sum'],
            'network_brand' => $this->resource['network_brand'],
            'network_id' => $this->resource['network_id'],
            'network_reason' => $this->resource['network_reason'],
            'phone_is_good' => $this->resource['phone_is_good'],
            'phone_is_good_reason' => $this->resource['phone_is_good_reason'],
            'name' => $this->resource['name'],
            'country_id' => $this->resource['country_id'],
            'state_id' => $this->resource['state_id'],
            'state_id_reason' => $this->resource['state_id_reason'],
            'custom1_str' => $this->resource['custom1_str'],
            'custom2_str' => $this->resource['custom2_str'],
            'custom3_str' => $this->resource['custom3_str'],
            'custom4_str' => $this->resource['custom4_str'],
            'custom5_str' => $this->resource['custom5_str'],
            'custom1_int' => $this->resource['custom1_int'],
            'custom2_int' => $this->resource['custom2_int'],
            'custom3_int' => $this->resource['custom3_int'],
            'custom4_int' => $this->resource['custom4_int'],
            'custom5_int' => $this->resource['custom5_int'],
            'custom1_dec' => $this->resource['custom1_dec'],
            'custom2_dec' => $this->resource['custom2_dec'],
            'custom1_datetime' => $this->resource['custom1_datetime'],
            'custom2_datetime' => $this->resource['custom2_datetime'],
            'custom3_datetime' => $this->resource['custom3_datetime'],
            'custom4_datetime' => $this->resource['custom4_datetime'],
            'custom5_datetime' => $this->resource['custom5_datetime'],
            'meta' => !empty($this->resource['meta']) ? json_decode($this->resource['meta'], true) : null,
            'date_created' => $this->resource['date_created'],
            'date_updated' => $this->resource['date_updated'],
            'is_deleted' => !empty($this->resource['is_deleted']),
        ];
    }
}
