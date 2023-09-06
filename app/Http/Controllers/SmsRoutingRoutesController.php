<?php

namespace App\Http\Controllers;

use App\Http\Resources\SmsRoutingRouteCollection;
use App\Http\Resources\SmsRoutingRouteResource;
use App\Models\SmsRoute;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Response;

/**
 * // @tags RoutingRoutes
 */
class SmsRoutingRoutesController extends Controller
{
    /**
     * @return SmsRoutingRouteCollection
     */
    public function index()
    {
        return new SmsRoutingRouteCollection(
            SmsRoute::where('team_id',
                auth()->user()->currentTeam->id)
                ->with(['smsRouteCompany', 'connection', 'smsRouteRates'])
                ->get()
        );
    }

    public function store(Request $request)
    {
        $route = SmsRoute::create($request->validate([
            'name' => 'required',
            'sms_route_company_id' => 'required',
            'connection_id' => 'required',
            'connection_type' => ['required', 'in:smpp'],
        ]));

        return SmsRoutingRouteResource::make($route);
    }

    public function destroy()
    {
        $route = SmsRoute::findOrFail(request()->route('route'));
        AuthService::isModelOwner($route);

        $route->delete();

        return Response::noContent();
    }
}
