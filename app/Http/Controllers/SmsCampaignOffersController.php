<?php

namespace App\Http\Controllers;

use App\Http\Resources\OfferCampaignResource;
use App\Models\OfferCampaign;
use App\Models\SmsCampaign;
use App\Services\AuthService;
use Illuminate\Http\Request;

class SmsCampaignOffersController extends Controller
{
    public function index(SmsCampaign $campaign)
    {
        AuthService::isModelOwner($campaign);

        return OfferCampaignResource::collection(OfferCampaign::where(['sms_campaign_id' => $campaign->id])->get());
    }

    public function store(Request $request, SmsCampaign $campaign)
    {
        $request->validate([
            'offer_id' => 'required|exists:offers,id',
        ]);
        AuthService::isModelOwner($campaign);
        $offer = OfferCampaign::create([
            'offer_id' => $request->offer_id,
            'sms_campaign_id' => $campaign->id,
        ]);

        return response(new OfferCampaignResource($offer), 201);
    }

    public function update(Request $request, SmsCampaign $campaign, OfferCampaign $offer)
    {
        AuthService::isModelOwner($campaign);
        $this->validate($request, [
            'is_active' => 'sometimes|boolean',
        ]);
        if ($offer->sms_campaign_id !== $campaign->id) {
            return response(null, 404);
        }

        $offer->update($request->only(['is_active']));

        return response(new OfferCampaignResource($offer), 200);
    }

    public function destroy(SmsCampaign $campaign, OfferCampaign $offer)
    {
        AuthService::isModelOwner($campaign);
        if ($offer->sms_campaign_id !== $campaign->id) {
            return response(null, 404);
        }
        $offer->delete();

        return response(null, 204);
    }
}
