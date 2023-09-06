<?php

namespace App\Http\Controllers;

use App\Http\Resources\SmsCampaignSenderidResource;
use App\Models\SmsCampaign;
use App\Models\SmsCampaignSenderid;
use App\Services\AuthService;
use Illuminate\Http\Request;

class SmsCampaignSenderidsController extends Controller
{
    public function index(SmsCampaign $campaign)
    {
        AuthService::isModelOwner($campaign);

        return SmsCampaignSenderidResource::collection(
            SmsCampaignSenderid::where(['sms_campaign_id' => $campaign->id])->get()
        );
    }

    public function store(Request $request, SmsCampaign $campaign)
    {
        $request->validate([
            'text' => 'required|regex:/^[a-zA-Z0-9]{3,11}$/',
        ]);
        AuthService::isModelOwner($campaign);
        $text = SmsCampaignSenderid::create([
            'text' => $request->text,
            'sms_campaign_id' => $campaign->id,
        ]);

        return response(new SmsCampaignSenderidResource($text), 201);
    }

    public function update(Request $request, SmsCampaign $campaign, SmsCampaignSenderid $senderid)
    {
        AuthService::isModelOwner($campaign);
        $this->validate($request, [
            'text' => 'sometimes|regex:/^[a-zA-Z0-9]{3,11}$/',
            'is_active' => 'sometimes|boolean',
        ]);
        if ($senderid->sms_campaign_id !== $campaign->id) {
            return response(null, 404);
        }

        $senderid->update($request->only(['text', 'is_active']));

        return response(new SmsCampaignSenderidResource($senderid), 200);
    }

    public function destroy(SmsCampaign $campaign, SmsCampaignSenderid $senderid)
    {
        AuthService::isModelOwner($campaign);
        if ($senderid->sms_campaign_id !== $campaign->id) {
            return response(null, 404);
        }
        $senderid->delete();

        return response(null, 204);
    }
}
