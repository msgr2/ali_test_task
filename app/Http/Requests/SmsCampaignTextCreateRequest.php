<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SmsCampaignTextCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sms_campaign_id' => ['required', 'integer'],
            'text' => ['required', 'string'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
