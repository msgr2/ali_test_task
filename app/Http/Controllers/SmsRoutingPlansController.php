<?php

namespace App\Http\Controllers;

use App\Data\SmsRoutingPlanSelectorData;
use App\Http\Resources\SmsRoutingPlanResource;
use App\Models\SmsRoutingPlan;
use App\Services\AuthService;
use App\Services\SendingProcess\Routing\SmsRoutingPlanSelectorService;
use Illuminate\Http\Request;

class SmsRoutingPlansController extends Controller
{
    public function index()
    {
        return SmsRoutingPlanResource::collection(
            SmsRoutingPlan::where(['team_id' => auth()->user()->currentTeam->id])->orderByDesc('created_at')->get()
        );
    }

    public function show(SmsRoutingPlan $plan)
    {
        AuthService::isModelOwner($plan);

        return new SmsRoutingPlanResource($plan);
    }

    public function store(Request $request)
    {
        $plan = SmsRoutingPlan::make(
            $request->validate([
                'name' => 'required|string',
            ])
        );
        $plan->team_id = auth()->user()->currentTeam->id;
        $plan->save();

        return response(new SmsRoutingPlanResource($plan), 201);
    }

    public function update(SmsRoutingPlan $plan, Request $request)
    {
        AuthService::isModelOwner($plan);

        $plan->update(
            $request->validate([
                'name' => 'required|string',
            ])
        );

        return new SmsRoutingPlanResource($plan);
    }

    public function destroy(SmsRoutingPlan $plan)
    {
        AuthService::isModelOwner($plan);
        $plan->delete();

        return response(null, 204);
    }

    /**
     * @param Request $request
     * @param SmsRoutingPlan $plan
     * @return array{status: string, selected_method: string,
     *     selected_rule_id: string|null, selected_action: string, selected_route_id: string|null, selector_data: SmsRoutingPlanSelectorData|null }
     */
    public function simulate(Request $request, SmsRoutingPlan $plan)
    {
        $params = $request->validate([
            'country_id' => 'required|integer|exists:countries,id',
            'network_id' => 'sometimes|integer|exists:networks,id',
            'counter' => 'sometimes|integer|min:0',
        ]);

        AuthService::isModelOwner($plan);
        $selector = SmsRoutingPlanSelectorService::createSelector($params['country_id'],
            $plan,
            $params['network_id'] ?? null,
            $params['counter'] ?? 0
        );

        return response()->json($selector, 200);
    }
}
