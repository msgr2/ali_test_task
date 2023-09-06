<?php

namespace App\Services\SendingProcess\Routing;

use api\v2\sms\routing\modules\plans\resources\enums\PlanActionEnum;
use api\v2\sms\routing\modules\plans\services\resources\enums\SelectorMethodEnum;
use api\v2\sms\routing\modules\plans\services\resources\enums\SelectorStatusEnum;
use App\Data\SmsRoutingPlanRuleSplitActionVarsData;
use App\Data\SmsRoutingPlanSelectedData;
use App\Data\SmsRoutingPlanSelectorData;
use App\Enums\SmsRoutingPlanRuleActionEnum;
use App\Enums\SmsRoutingPlanSelectedMethodEnum;
use App\Enums\SmsRoutingPlanSelectedStatusEnum;
use App\Enums\SmsRoutingPlanSelectorFailStatusEnum;
use App\Models\SmsRoute;
use App\Models\SmsRoutingPlan;
use App\Models\SmsRoutingPlanRule;
use App\Services\CampaignSmsMessage;
use App\Services\RoutingPlansResource;
use App\Services\RoutingPlansRulesStats;
use App\Services\SelectRouteAutoService;
use App\Services\SendingProcess\Data\BuildSmsData;
use App\Services\UserRoutesService;
use Exception;
use Illuminate\Support\Facades\Log;
use Yii;

class SmsRoutingPlanSelectorService
{
    public static function createSelectorFromCampaginMsg(CampaignSmsMessage $msg)
    {
        $plan = RoutingPlansResource::find($msg->lapObj->getGateway()->id)->first();

        return self::createSelectorForBuildSms($plan, $msg->lapObj->country, $msg->counter);
    }

    /**
     * Todo: add network support
     *
     * @param RoutingPlansResource|null $plan
     * @param mixed $country
     * @param $counter
     * @return SmsRoutingPlanSelectorData|null
     * @throws Exception
     */
    public static function createSelectorForBuildSms(
        SmsRoutingPlan $plan,
        BuildSmsData   $data,
    )
    {
        Log::debug("create selector");

        $selectorData = SmsRoutingPlanSelectorData::from([
            'country_id' => $data->sendToBuildSmsData->country_id,
            'plan_id' => $plan->id,
            'team_id' => $plan->team_id,
            'counter' => $data->sendToBuildSmsData->counter,
        ]);

        $response = self::makeDecision($selectorData);
        Log::debug("selector response", ['response' => $response, 'selector' => $selectorData]);
        return $response;
    }

    /**
     * Will return SmsRoutingPlanSelectedData if selected or SmsRoutingPlanSelectorData if not selected (failed)
     *
     * @param SmsRoutingPlanSelectorData $selector
     * @return SmsRoutingPlanSelectedData|SmsRoutingPlanSelectorData
     */
    private static function makeDecision(SmsRoutingPlanSelectorData $selector):
    SmsRoutingPlanSelectedData|SmsRoutingPlanSelectorData
    {
        if ($selected = self::setByPlanSelector($selector)) {
            return $selected;
        }
        if ($selected = SmsRoutingPlanAutoSelectorService::selectRoute($selector)) {
            return $selected;
        }

        Log::debug('No matching rules found', ['selector' => $selector]);
        $selector->fail_status = SmsRoutingPlanSelectorFailStatusEnum::no_route_found();
        return $selector;
    }

    public static function setByPlanSelector(SmsRoutingPlanSelectorData $selector, $counter = 0):
    SmsRoutingPlanSelectedData|false
    {
        $rule = self::getRule($selector);
//        if ($counter === 3) {
//            dd(SmsRoutingPlanRule::where(['sms_routing_plan_id' => $selector->plan->id])
//                ->where(['country_id' => $selector->country_id])
//                ->whereNotIn('id', $selector->filtered_route_ids)
//                ->where('is_active', true)
//                ->orderBy('country_id', 'asc')
//                ->orderBy('network_id', 'asc')
//                ->orderBy('priority', 'asc')
//                ->toSql());
//            dd(3, $selector);
//        }
        if ($rule) {
            $setRuleResponse = self::setByRule($rule, $selector);
            if ($setRuleResponse instanceof SmsRoutingPlanSelectedData) {
                return $setRuleResponse;
            }

            return self::setByPlanSelector($setRuleResponse, ++$counter);
        }

        Log::debug("rule not found not found");
        return false;
    }

    private static function getRule(SmsRoutingPlanSelectorData $selector)
    {
        return SmsRoutingPlanRule::where(['sms_routing_plan_id' => $selector->plan_id])
            ->where(['country_id' => $selector->country_id])
            ->whereNotIn('id', $selector->filtered_rules_ids)
            ->whereNotIn('sms_routing_plan_id', $selector->filtered_route_ids)
            ->where('is_active', true)
            ->orderBy('country_id', 'asc')
            ->orderBy('network_id', 'asc')
            ->orderBy('priority', 'asc')
            ->first();

        $rule = $selector
            ->plan
            ->planRules()
            ->bySelectOrder()
            ->where(['country_id' => $selector->country_id])
            ->whereNotIn('id', $selector->filtered_route_ids)
            ->first();
        Log::debug("rulesC", ['is_found' => empty($rule)]);
        return $rule;
    }

    private static function setByRule(SmsRoutingPlanRule         $rule,
                                      SmsRoutingPlanSelectorData $selector): SmsRoutingPlanSelectedData|SmsRoutingPlanSelectorData
    {
        $selected = SmsRoutingPlanSelectedData::from([
            'selected_method' => SmsRoutingPlanSelectedMethodEnum::rules(),
            'selected_action' => $rule->action,
            'selected_rule' => $rule,
            'selected_rule_id' => $rule->id,
            'status' => SmsRoutingPlanSelectedStatusEnum::success()->value,
        ]);
        if ($rule->action === SmsRoutingPlanRuleActionEnum::send()->value) {
            if (in_array($rule->sms_route_id, $selector->filtered_route_ids)) {
                $selector->filtered_rules_ids[] = $rule->id;
                return $selector;
            }

            $route = $rule->smsRoute;
            if (!$route->hasRateForCountry($selector->country_id)) {
                $selector->filtered_rules_ids[] = $rule->id;
                return $selector;
            }

            $selected->route_rate = $route->priceForCountry;
            $selected->selected_route_id = $rule->sms_route_id;
            return $selected;
        }
        if ($rule->action === SmsRoutingPlanRuleActionEnum::split()->value) {
            return self::setByRuleSplit($rule, $selector, $selected);
        }
        if ($rule->action === SmsRoutingPlanRuleActionEnum::filter()->value) {
            $selector->filtered_route_ids[] = $rule->sms_route_id;
            $selector->filtered_rules_ids[] = $rule->id;
            return $selector;
        }
        //todo add support for network, split, filter and drop

        return $selected;
    }

    private static function setByRuleSplit(SmsRoutingPlanRule         $rule, SmsRoutingPlanSelectorData $selector,
                                           SmsRoutingPlanSelectedData $selected)
    {
        if ($selector->counter === false) {
            return $selected;
        }

        $splitAction = SmsRoutingPlanRuleSplitActionVarsData::from($rule->action_vars);
        if ($splitAction->limit > 0) {
            //todo check how many times been used and if passed it than disable rule for next time.
        }

        #remove $selector->filtered_route_ids frp, $splitAction->route_ids
        $splitRoutes = array_values(array_diff($splitAction->route_ids, $selector->filtered_route_ids));
        $splitRoutes = array_values(array_filter($splitRoutes, function ($routeId) use ($selector) {
            return !in_array($routeId, $selector->filtered_route_ids);
        }));
        if (empty($splitRoutes)) {
            $selector->filtered_rules_ids[] = $rule->id;
            return $selector;
        }

        $selected->selected_route_id = $splitRoutes[$selector->counter % count($splitRoutes)];

        return $selected;
    }

    public static function createSelector($country_id, SmsRoutingPlan $plan, $networkId = false,
                                          $counter = 0): SmsRoutingPlanSelectedData|SmsRoutingPlanSelectorData
    {
        $selectorData = SmsRoutingPlanSelectorData::from([
            'country_id' => $country_id,
            'plan_id' => $plan->id,
            'team_id' => $plan->team_id,
            'counter' => $counter,
        ]);

        $selectedData = self::makeDecision($selectorData);
        if ($selectedData instanceof SmsRoutingPlanSelectorData) {
            //todo: log failed to select data

            return $selectedData;
        }
        $selectedData->selector_data = $selectorData;
        return $selectedData;
    }

    public static function saveSelectorMsgToStats(CampaignSmsMessage $msg, SmsRoutingPlanSelectorData $selector)
    {
        $request = $selector->request;
        $selector->request = null;
        $stats = new RoutingPlansRulesStats();
        $stats->fill([
            'user_id' => $msg->lapObj->getCampaign()->user_id,
            'lap_id' => $msg->lapObj->id,
            'rule_id' => $selector->selected_rule,
            'final_action' => $selector->selected_action,
            'is_auto' => $selector->isAutoRouteUsed(),
            'rand_key' => $msg->random_key,
            'request' => json_encode($request),
            'selector' => json_encode($selector)
        ]);
        if (!$stats->save()) {
            throw new Exception('Failed to save stats');
        }
    }

    private function getRulesByOrder()
    {
        return $this->hasMany(SmsRoutingPlanRule::class, ['plan_id' => 'id'])
            ->orderBy(['priority' => SORT_ASC]);
    }
}