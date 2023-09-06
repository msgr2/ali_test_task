<?php

namespace App\Http\Controllers;

use App\Enums\SmsRoutingLogActionsEnum;
use App\Http\Resources\SmsRoutingLogsCollection;
use App\Http\Resources\SmsRoutingRateResource;
use App\Models\SmsRoute;
use App\Models\SmsRouteRate;
use App\Models\SmsRoutingLogs;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Response;

class SmsRoutingRatesController extends Controller
{
    /**
     * @param array $with expands json information Optional ['smsRoute', 'country', 'network']
     */
    public function index(Request $request)
    {
        $request->validate([
            'with' => 'sometimes|array',
            'with.*' => 'sometimes|in:smsRoute',
            'country_id' => 'sometimes|exists:countries,id',
            'network_id' => 'sometimes|exists:mobile_networks,id',
        ]);

        return SmsRoutingRateResource::collection(
            auth()->user()->currentTeam->smsRouteRates()
                ->when($request->has('country_id'), function ($query) use ($request) {
                    $query->where(['country_id' => $request->get('country_id')]);
                })
                ->when($request->has('network_id'), function ($query) use ($request) {
                    $query->where(['network_id' => $request->get('network_id')]);
                })
                //add when with[]
                ->when($request->has('with'), function ($query) use ($request) {
                    $query->with($request->get('with'));
                })
                ->get()
        );
    }

    public function store(Request $request)
    {
        $params = $request->validate([
            'rate' => 'required|numeric',
            'country_id' => 'required|exists:countries,id',
            'sms_route_id' => 'required|exists:sms_routes,id',
            'network_id' => 'exists:mobile_networks,id',
        ]);

        AuthService::isModelOwner(SmsRoute::where(['id' => $params['sms_route_id']])->first());

        SmsRoutingLogs::create([
            'action' => SmsRoutingLogActionsEnum::new_rate(),
            'sms_route_id' => $params['sms_route_id'],
            'country_id' => $params['country_id'],
            'old_rate' => 0,
            'network_id' => $params['network_id'] ?? null,
            'new_rate' => $params['rate'],
            'team_id' => auth()->user()->currentTeam->id,
            'user_id' => auth()->user()->id,
        ]);

        return Response::json(SmsRoutingRateResource::make(SmsRouteRate::create($params)), '201');
    }

    public function update(SmsRouteRate $rate, Request $request)
    {
        $params = $request->validate([
            'rate' => 'required|numeric',
        ]);
        AuthService::isModelOwner($rate->smsRoute);

        SmsRoutingLogs::create([
            'action' => SmsRoutingLogActionsEnum::update_rate(),
            'sms_route_id' => $rate->sms_route_id,
            'country_id' => $rate->country_id,
            'old_rate' => $rate->rate,
            'network_id' => $rate->network_id,
            'new_rate' => $params['rate'],
            'team_id' => auth()->user()->currentTeam->id,
            'user_id' => auth()->user()->id,
        ]);

        $rate->update(['rate' => $params['rate']]);

        return new SmsRoutingRateResource($rate);

    }

    public function logs()
    {
        return new SmsRoutingLogsCollection(
            SmsRoutingLogs::where(['team_id' => auth()->user()->currentTeam->id])->get()
        );
    }
}
