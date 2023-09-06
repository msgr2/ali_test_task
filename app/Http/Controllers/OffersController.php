<?php

namespace App\Http\Controllers;

use App\Http\Resources\OfferResource;
use App\Models\Offer;
use App\Services\AuthService;
use Illuminate\Http\Request;

class OffersController extends Controller
{
    public function index()
    {
        return OfferResource::collection(Offer::where(['team_id' => auth()->user()->current_team_id])->get());
    }

    public function store(Request $request)
    {
        $params = $request->validate([
            'name' => ['required', 'string'],
            'url' => ['required', 'url'],
            'profit' => ['sometimes', 'numeric'],
        ]);

        $offer = Offer::make($params);
        $offer->team_id = auth()->user()->current_team_id;
        $offer->save();

        return response(new OfferResource($offer), 201);
    }

    public function update(Request $request, Offer $offer)
    {
        $params = $request->validate([
            'name' => ['sometimes', 'string'],
            'url' => ['sometimes', 'url'],
            'profit' => ['sometimes', 'numeric'],
        ]);

        AuthService::isModelOwner($offer);

        $offer->update($params);

        return response(new OfferResource($offer), 200);
    }

    public function destroy($id)
    {
        $offer = Offer::findOrFail($id);

        AuthService::isModelOwner($offer);

        $offer->delete();

        return response(null, 204);
    }
}
