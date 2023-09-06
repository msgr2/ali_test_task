<?php

namespace App\Services;

use api\v2\sms\sends\campaigns\sending_services\DomainsService;
use api\v2\sms\sends\campaigns\sending_services\models\CampaignSmsMessage;
use App\Enums\LogContextEnum;
use App\Exceptions\CampaignSendException;
use App\Models\OfferCampaign;
use App\Services\SendingProcess\Data\BuildSmsData;
use common\components\Shorty;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UrlShortenerService
{
    /**
     * @param  $domains
     * @throws Exception
     */
    public static function setShortlink(BuildSmsData $msg)
    {
        Log::debug('Setting shortlink', ['sms_campaign_send_id' => $msg->sendToBuildSmsData->sms_campaign_send_id]);
        if (env('dont_send', false)) {
            throw new Exception('dont_send');
        }

        $offer = self::selectOffer($msg);
        $msg->selectedOffer = $offer;

        Log::debug('URL shortener - selected offer', ['offer_id' => $offer->id]);
        $urlShortenerParams = self::buildUrlShortenerPostParams($msg);

        Log::debug('URL shortener - got params', ['params' => $urlShortenerParams]);
        $domain = DomainService::getDomainForCampaign($msg);

        Log::debug('URL shortener - got domain', ['domain_id' => $domain->id, 'domain_url' => $domain->domain]);
        if (!$domain) {
            throw new Exception('Failed to get shorty domain');
        }

        $msg->domain = $domain;

        $link = self::callUrlShortener($msg, $urlShortenerParams);
        if (empty($link)) {
            throw new CampaignSendException('Failed to create shortlink');
        }

        Log::debug("Received shortlink: $link");
        $msg->sms_shortlink = $link;
        $msg->sms_optout_link = $msg->getOptOutLink();

        return true;
    }

    private static function selectOffer(BuildSmsData $msg)
    {
        $offers = OfferCampaign::where(['sms_campaign_id' => $msg->sendToBuildSmsData->sms_campaign_id, 'is_active' =>
            true])
            ->get();
        if ($offers->isEmpty()) {
            throw new Exception('No offers found for campaign');
        }

        return $offers[$msg->sendToBuildSmsData->counter % count($offers)]->offer;
    }//end getShortlink()

    private static function buildUrlShortenerPostParams(BuildSmsData $msg)
    {
        $neededParams = $msg->selectedOffer->getNeededParams();
        if (!$neededParams) {
            return [];
        }
        $replacementParams = $msg->getReplacementParams();
        foreach ($neededParams as $neededParam) {
            if (isset($replacementParams[$neededParam])) {
                $postParams[$neededParam] = $replacementParams[$neededParam];
            }
        }

        Log::debug('Shorty request params:', ['params' => $postParams]);
        return $postParams;
    }//end buildShortyPostParams()

    private static function callUrlShortener(BuildSmsData $msg, $shortyParams = [])
    {
        $i = 0;
        $res = null;
        do {
//            return "local.dev/abc";
            //no need to create model.. i think can be called from here.

            $res = self::createKeyword(
                $msg->selectedOffer->url,
                $msg->domain->domain,
                $msg->sendToBuildSmsData->sms_campaign_id,
                [
                    'sms_id' => $msg->sms_id,
                    ...$shortyParams,
                ],
            );

            if ($res) {
                break;
            }

//            echo '.';
            usleep(rand(200000, 700000));

            if ($i == 20) {
                throw new Exception("Failed to short link");
            }

            $i++;
        } while (!$res);

        Log::debug("Shorty response: ", ['response' => $res]);
        $msg->keyword_data = $res['data'];
        $msg->scheme = 'http';

        if (!isset($shortyParams['is_http'])) {
            if ($msg->domain->https_support) {
                $msg->scheme = 'https';
            }
        }

        return $msg->getShortLink();
    }

    private static function createKeyword($link, $domain, $campaignId, $meta)
    {
        $data = [
            'link' => $link,
            'domain' => $domain,
            'campaign_id' => $campaignId,
            'meta' => $meta,
        ];

        $url = rtrim(config('services.shortener.url'), '/') . '/api/short-url';
        Log::debug('calling url shortener', ['url' => $url, 'data' => $data]);
        $response = Http::withBody(json_encode($data))
            ->post($url);

        if ($response->created()) {
            return $response->json();
        }

        Log::error('Failed to create keyword', [
            'request_data' => $data,
            'response' => $response->json(),
        ]);

        return null;
    }

    public static function getDynamicSmsOptOut($link)
    {
        return self::getDynamic($link, 'o');
    }

    private static function getDynamic($link, $prefix)
    {
        $parsed = parse_url($link);
        $dynamic = '';

        if (!empty($parsed['scheme']) && !empty($parsed['host']) && !empty($parsed['path'])) {
            $keyword = ltrim($parsed['path'], '/');
            $dynamic = $parsed['scheme'] . '://' . $parsed['host'] . '/' . trim($prefix, '/') . '/' . $keyword;
        }

        return $dynamic;
    }
}
