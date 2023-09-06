<?php

namespace App\Http\Controllers;

use App\Http\Resources\SmsRouteSmppConnectionResource;
use App\Models\SmsRouteSmppConnection;
use App\Services\SendingProcess\Telecom\SMPP\SmppClient;
use App\Services\SmppService;
use Illuminate\Http\Request;

class SmsRoutingSmppConnectionsController extends Controller
{
    public function store(Request $request): SmsRouteSmppConnectionResource
    {
        return SmsRouteSmppConnectionResource::make(SmsRouteSmppConnection::create(
            $request->validate(SmsRouteSmppConnection::$rules)
        ));
    }

    public function test(Request $request)
    {
        return [
            'success' =>
                SmppClient::testConnection(
                    SmsRouteSmppConnection::make($request->validate(SmsRouteSmppConnection::$rules))),
        ];
    }

    public function show(SmsRouteSmppConnectionResource $smsRouteSmppConnection)
    {
        if ($smsRouteSmppConnection->smsRoute->team_id !== auth()->user()->currentTeam->id) {
            abort(403);
        }

        return $smsRouteSmppConnection;
    }
}
