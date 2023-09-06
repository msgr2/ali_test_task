<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SmsRoutingRouteCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required',
            'description' => 'nullable',
            'sms_route_company_id' => 'required',
            'smpp_connection_id' => 'required',
        ];
    }
}
