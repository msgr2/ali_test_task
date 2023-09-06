<?php

namespace App\Services\SendingProcess;

use App\Exceptions\CampaignSendException;
use App\Services\SendingProcess\Data\BuildSmsData;
use App\Services\UrlShortenerService;
use App\Models\SmsCampaignText;
use Illuminate\Support\Facades\Log;
use SMSCounter;
use Str;

class TextService
{
    private static BuildSmsData $data;

    public static function processMsg(BuildSmsData $data)
    {
        self::$data = $data;
        $data->selectedCampaignText =
            self::getSpecificAdText($data->sendToBuildSmsData->sms_campaign_id, $data->sendToBuildSmsData->counter);

        self::processTextReplacement();
    }

    private static function getSpecificAdText($campaign_id, $counter)
    {
        $adTexts = SmsCampaignText::where(['sms_campaign_id' => $campaign_id, 'is_active' => 1])->get();
        if ($adTexts->isEmpty()) {
            Log::debug('No ad texts found for campaign: ' . $campaign_id);
            throw new CampaignSendException('No ad texts found for campaign: ' . $campaign_id);
        }
        return $adTexts[($counter % $adTexts->count())];
    }

    private static function processTextReplacement()
    {
        if (self::$data->selectedCampaignText->haveDomainOrOptoutTag()) {
            UrlShortenerService::setShortlink(self::$data);
//            self::$data->sms_optout_link = UrlShortenerService::getDynamicSmsOptOut(self::$data->sms_shortlink);
        }

        $text = self::prepareText();

        return $text;
    }

    private static function prepareText()
    {
        $text = self::$data->selectedCampaignText->text;
        Log::debug('prepareText', ['text' => $text]);
        self::setIsInitialMsgLong($text);

        $text = self::mandatoryTextReplacements($text);
        Log::debug('after mandatory', ['text' => $text]);
        $text = self::optionalTextReplacements($text);
        Log::debug('after optional', ['text' => $text]);
        $text = self::metaTextReplacements($text);
        Log::debug('after meta', ['text' => $text]);
        $text = self::replaceRandomDigits($text);
        Log::debug('after rand-digits', ['text' => $text]);
        $text = self::processSpintext($text);
        Log::debug('after spintext', ['text' => $text]);
        $text = self::cleanTextFromShortcodes($text);
        Log::debug('after cleantextfromshortcodes', ['text' => $text]);

        //todo - refactor out number replacement and clean text.. translations as part of routing plans
//        $text = Translations::replaceMessage($->lapObj->getCampaign()->user->translationMessage(), $text);
        self::$data->finalText = $text;
        self::$data->final_text_is_unicode = self::isUnicode($text);
        self::$data->final_text_msg_parts = self::getParts($text);
        Log::debug('Final text', ['text' => $text]);

        return $text;
    }

    private static function setIsInitialMsgLong(mixed $text)
    {
        $parts = self::getParts($text);
        self::$data->submited_text_parts = $parts;
        if ($parts > 1) {
            Log::debug('message too long');
            self::$data->is_initial_msg_long = true;
        }
    }

    public static function getParts(string $text): int
    {
        $msgs = (new SMSCounter())->count($text);

        return $msgs->messages;
    }

    public static function mandatoryTextReplacements($text)
    {
        $shortlink = self::$data->sms_shortlink;

        if (!empty(self::$data->sms_optout_link) && str_contains($text, '{optout}')) {
            $optout = self::$data->sms_optout_link;
            $text = str_replace('{optout}', $optout, $text);
        }

        if (str_contains($text, '{domain}')) {
            $text = str_replace('{domain}', $shortlink, $text);
        }

        $text = str_replace("\n", ' ', $text);
        $text = preg_replace('~\s+~', ' ', $text);

        if (self::getParts($text) > self::$data->submited_text_parts) {
            Log::warning(
                'Initial msg was short after replacing was long - mandatory',
                ['original' => self::$data->selectedCampaignText->text, 'replaced' => $text]
            );
        }

        $text = self::cleanBadSymbols($text);
        $text = htmlspecialchars_decode($text);

//        $trim_gsm = intval(self::$_msg->lapObj->getGateway()->getType()->one()->trim_gsm);
//        if (!self::$_msg->selectedCampaignText->isUnicode() && $trim_gsm) {
//            $text = GsmEncoder::utf8_to_gsm0338_transliterate($text);
//            Yii::$app->logger->logDebug("Trimmed Message: $text");
//        }

        return $text;
    }//end setFinalText()

    private static function cleanBadSymbols(string $msg)
    {
        $message = preg_replace(
            [
                '/\x{008C}/u',
                '/\x{0080}/u',
                '/\x{0081}/u',
                '/\x{0082}/u',
                '/\x{0083}/u',
                '/\x{0084}/u',
                '/\x{0085}/u',
                '/\x{0086}/u',
                '/\x{0087}/u',
                '/\x{0088}/u',
                '/\x{0089}/u',
                '/\x{008B}/u',
                '/\x{008D}/u',
                '/\x{008E}/u',
                '/\x{008F}/u',
                '/\x{0090}/u',
                '/\x{0091}/u',
                '/\x{0092}/u',
                '/\x{0093}/u',
                '/\x{0094}/u',
                '/\x{0095}/u',
                '/\x{0096}/u',
                '/\x{0097}/u',
                '/\x{0098}/u',
                '/\x{0099}/u',
                '/\x{009A}/u',
                '/\x{009B}/u',
                '/\x{009C}/u',
                '/\x{009D}/u',
                '/\x{009E}/u',
                '/\x{009F}/u',
            ],
            '',
            $msg
        );

        // users very often put this ...
        $message = preg_replace('/\x{2019}/u', "'", $message);
        // right single quotation mark
        $message = preg_replace('/\x{00A0}/u', ' ', $message);
        // no-break space
        return $message;
    }//end selectSpecificAdText()

    public static function optionalTextReplacements(string $text)
    {
        $originalText = $text;
//        $route_name = self::$_msg->lapObj->getGateway()->name;
        $route_name = 'ROUTE_TODO';
        //todo: after route is implemented, change this to route name

        $shortcodes = [
            '{sms_id}' => self::$data->sms_id,
            '{phone}' => self::$data->sendToBuildSmsData->phone_normalized,
            '{ad_id}' => self::$data->selectedCampaignText->id,
            '{dayofweek}' => date('l'),
            '{ROUTE}' => $route_name,
            '{route}' => $route_name,
        ];
        foreach ($shortcodes as $shortcode => $replacement) {
            $text = str_replace($shortcode, $replacement, $text);
        }

        if (self::getParts($text) > self::getParts($originalText)) {//if bigger clean text from previous shortcodes..
            $text = $originalText;
            foreach ($shortcodes as $shortcode => $replacement) {
                $text = str_replace($text, $shortcode, ' ');
            }
        }
        return $text;
    }//end optionalTextReplacements()

    private static function metaTextReplacements(mixed $text)
    {
        $params = self::$data->getReplacementParams();
        foreach ($params as $shortcode => $val) {
            $textBefore = $text;
            $text = self::metaTextReplacement($text, $shortcode, $val);
            if (self::getParts($text) > self::getParts($textBefore)) {
                $text = self::metaTextReplacement($textBefore, $shortcode, '');
            }
        }

        return $text;
    }//end mandatoryTextReplacements()

    private static function metaTextReplacement($text, $shortcode, $metaVal)
    {
        if (str_contains($text, "\{$shortcode\}" && !empty($metaVal))
        ) {
            $originalText = $text;
            $originalParts = self::getParts($text);
//            $metaVal = $meta ?? ucfirst(strtolower(explode(' ', $meta[$shortcode])));
            $text = str_replace("\{$shortcode\}", $metaVal, $text);

            if (self::getParts($text) > $originalParts) {
                // string in name might be utf-8 encoded
                $text = str_replace("\{$shortcode\}", mb_substr($metaVal, 0, 6, 'UTF-8'), $originalText);
                if (self::getParts($text) > $originalParts) {
                    $text = str_replace("\{$shortcode\}", '', $originalText);
                }
            }
        }

        return $text;
    }//end getParts()

    private static function replaceRandomDigits($text)
    {
        // replaces {rand} with random 3 letter string, max 8 times
        for ($i = 0; str_contains($text, '{rand}') && $i < 8; $i++) {
            $text = preg_replace('/{rand}/', Str::random(1), $text, 1);
        }

        for ($i = 0; str_contains($text, '{d}') && $i < 32; $i++) {
            $text = preg_replace('/{d}/', rand(0, 9), $text, 1);
        }

        return $text;
    }

    private static function processSpintext(mixed $text)
    {
        //        preg_match_all('/\{{{[\s\S]*\|[\s\S\|]*\}}}/isU', $text, $m);
        preg_match_all('/\{{3}[^\{]*\|.*\}{3}/isU', $text, $m);

        if (isset($m[0])) {
            foreach ($m[0] as $spin) {
                $tmp = str_replace('{{{', '', $spin);
                $tmp = str_replace('}}}', '', $tmp);
                $tmp_arr = explode('|', $tmp);
                $tmp_arr = array_filter($tmp_arr, function ($v) {
                    $v = trim($v);
                    if (empty($v)) {
                        return false;
                    }
                    return true;
                });
                if (!empty($tmp_arr)) {
                    $k = array_rand($tmp_arr, 1);
                    $replace = trim($tmp_arr[$k]);
                } else {
                    $replace = '';
                }
                $text = str_replace($spin, $replace, $text);
            }
        }
        return $text;
    }

    private static function cleanTextFromShortcodes($text)
    {
        $text = preg_replace('/{{{.*?}}}/', '', $text);
        return preg_replace('/{.*?}/', '', $text);
    }//end findBadSymbols()

    public static function isUnicode($text)
    {
        $text = str_replace('+', 'A', $text);
        //$res = \SMSCounter::count($text);
        $res = (new SMSCounter())->count($text);
        return $res->per_message < 71;
    }
}
