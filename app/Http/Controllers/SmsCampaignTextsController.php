<?php

namespace App\Http\Controllers;

use App\Http\Requests\SmsCampaignTextCreateRequest;
use App\Http\Resources\SmsCampaignTextResource;
use App\Models\SmsCampaign;
use App\Models\SmsCampaignText;
use App\Services\AuthService;
use Illuminate\Http\Request;

class SmsCampaignTextsController extends Controller
{
    public function index(SmsCampaign $campaign)
    {
        AuthService::isModelOwner($campaign);

        return SmsCampaignTextResource::collection(SmsCampaignText::where(['sms_campaign_id' => $campaign->id])->get());
    }

    public function store(SmsCampaign $campaign, Request $request)
    {
        $request->validate([
            'text' => 'required',
        ]);
        AuthService::isModelOwner($campaign);
        $text = SmsCampaignText::create([
            'text' => $request->text,
            'sms_campaign_id' => $campaign->id,
        ]);

        return response(new SmsCampaignTextResource($text), 201);
    }

    public function update(Request $request, SmsCampaign $campaign, SmsCampaignText $text)
    {
        $request->validate([
            'text' => 'sometimes',
            'is_active' => 'sometimes|boolean',
        ]);
        AuthService::isModelOwner($campaign);
        if ($text->sms_campaign_id !== $campaign->id) {
            return response(null, 404);
        }

        $text->update($request->only(['text', 'is_active']));

        return response(new SmsCampaignTextResource($text), 200);
    }

    public function destroy(Request $request, SmsCampaign $campaign, SmsCampaignText $text)
    {
        AuthService::isModelOwner($campaign);
        if ($text->sms_campaign_id !== $campaign->id) {
            return response(null, 404);
        }
        $text->delete();

        return response(null, 204);
    }
}
