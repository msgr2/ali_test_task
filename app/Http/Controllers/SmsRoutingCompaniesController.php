<?php

namespace App\Http\Controllers;

use App\Http\Resources\SmsRouteCompaniesCollection;
use App\Http\Resources\SmsRouteCompanyResource;
use App\Models\SmsRouteCompany;
use Illuminate\Http\Request;

class SmsRoutingCompaniesController extends Controller
{
    public function index()
    {
        return new SmsRouteCompaniesCollection(
            SmsRouteCompany::query()
                ->where(['team_id' => auth()->user()->current_team_id])
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }

    public function store(Request $request)
    {
        return SmsRouteCompanyResource::make(SmsRouteCompany::create($request->validate(SmsRouteCompany::$rules)));
    }
}
