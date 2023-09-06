<?php

namespace App\Services;

use App\Models\Country;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;

class NumberService
{
    public static PhoneNumberUtil $phoneUtil;

    public static array $good_countries_verify = [
//        'dk' => '/^45(2|30|31|40|41|42|50|51|52|53|60|61|71|81|91|93)\d{6}$/',
        'fr' => '/^33(6|7)\d{8}$/',
        'se' => '/^467(0|2|3|5|6|9)\d{7}$/',
        //sweden
        'uk' => '/^447(1|2|3|4|5|6|7|8|9)\d{8}$/',
        'gb' => '/^447(1|2|3|4|5|6|7|8|9)\d{8}$/',
//        'de' => '/^491((5\d{8,9})|(609\d{7})|(6([1-9])\d{7})|(76\d{8})|(7(0|1|2|3|4|5|7|8|9)\d{7}))$/',
        'it' => '/^393(2|3|4|6|8|9)\d{8}$/',
        'us' => '/^1\d{10}$/',
        'ca' => '/^1\d{10}$/',
        'ru' => '/^79\d{9}$/',
        //        'au' => '/^61(2|4|3|7|8)\d{8}$/', //bad verification, http://www.411.com/phone/61-7-5529-7911 is landline
//        'za' => '/^27[87]\d{9}$/',
//        'sg' => '/^[9|8][0-9]{7}$/',
        'nl' => '/^316\d{8}$/',
        'jp' => '/^81[789]0\d{8}/',
        //https://en.wikipedia.org/wiki/Telephone_numbers_in_Japan#Non-geographic_area_codes , http://www.livinglanguage.com/blog/2012/09/30/japanese-phone-numbers/
        'be' => '/^324(7\d{7}|8\d{7}|9\d{7}|55\d{6}|56\d{6}|60\d{6}|65\d{6}|66\d{6}|67\d{6}|68\d{6})/',
        //belgium, https://en.wikipedia.org/wiki/Telephone_numbers_in_Belgium#Mobile_numbers
        //        'fi' => '',
        //        'nz' => '',
        //        'no' => '',
        //        'ch' => '',
        //        'ie' => '',
        //        'es' => '',
        //        'cz' => '', //czech
        //        'sa' => '', //saudi arabia
        //        'ae' => '', //arab emirates
    ];

    private static array $isoCache = [];

    public static function isLogicalNumber($num): bool
    {
        if (strlen($num) < 7
            || strlen($num) > 16
            || !is_numeric($num)
            || preg_match('/(1{5}|2{5}|3{5}|4{5}|5{5}|6{5}|7{5}|8{5}|9{5}|0{5}|123456|56789|98765|54321)/', $num)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $number phone number string
     * @param string $country ISO or country name or country id
     * @return PhoneNumber|null
     */
    public static function normalize(string $number, string $country): ?PhoneNumber
    {
        $phoneNumber = self::phoneUtilParse($number, $country);

        if (empty($phoneNumber) && $country == 'fixed') {
            $numberString = "+$number";
            $phoneNumber = self::phoneUtilParse($numberString, $country);
        }

        if (!empty($phoneNumber) && !self::$phoneUtil->isValidNumber($phoneNumber)) {
            $countryCode = $phoneNumber->getCountryCode();
            $numberString = preg_replace('/^' . $countryCode . '/', '', $number);

            $phoneNumber = self::phoneUtilParse($numberString, $country);
        }

        if (empty($phoneNumber)) {
            return null;
        }

        if (self::$phoneUtil->isValidNumber($phoneNumber)) {
            return $phoneNumber;
        }

        if ($country == 'fixed') {
            $numberString = "+$number";
            $phoneNumber = self::phoneUtilParse($numberString, $country);
            if (self::$phoneUtil->isValidNumber($phoneNumber)) {
                return $phoneNumber;
            }
        }

        return null;
    }

    public static function phoneUtilParse($numberString, $country): ?PhoneNumber
    {
        $iso = self::getCountryISO($country);

        if (empty(self::$phoneUtil)) {
            self::$phoneUtil = PhoneNumberUtil::getInstance();
        }

        try {
            return self::$phoneUtil->parse($numberString, $iso);
        } catch (NumberParseException) {
            return null;
        }
    }

    private static function getCountryISO(?string $value): ?string
    {
        if (empty($value) || $value == 'fixed') {
            return null;
        }

        if (isset(self::$isoCache[$value])) {
            return self::$isoCache[$value];
        }

        if ($countryId = CountryService::guessCountry($value)) {
            $model = Country::find($countryId);
            self::$isoCache[$model->id] = $model->iso;

            return $model->iso;
        }

        return null;
    }

    public static function isMobile($normalized, $country): ?bool
    {
        if (preg_match('/^[a-zA-Z]{2}$/', $country)) {
            $iso = strtoupper($country);
        } else {
            $iso = self::getCountryISO($country);
        }

        if (!isset(self::$good_countries_verify[$iso])) {
            $phoneNumber = self::phoneUtilParse('+' . trim($normalized), $iso);

            if (empty($phoneNumber)) {
                return null;
            }

            $type = self::$phoneUtil->getNumberType($phoneNumber);

            $isMobilePhoneLib = in_array($type, [PhoneNumberType::MOBILE, PhoneNumberType::FIXED_LINE_OR_MOBILE]);
            if (!$isMobilePhoneLib && $iso == 'MX') {
                if (strlen($normalized) == 12 || strlen($normalized) == 13) {
                    return true;
                }
            }

            return $isMobilePhoneLib;
        }

        return (bool)preg_match(self::$good_countries_verify[$iso], trim($normalized));
    }
}
