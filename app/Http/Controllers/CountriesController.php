<?php

namespace App\Http\Controllers;

use App\Http\Resources\CountryResource;
use App\Models\Country;
use Illuminate\Http\Request;

class CountriesController extends Controller
{
    public function index()
    {
        return CountryResource::collection(Country::get());
    }
}
