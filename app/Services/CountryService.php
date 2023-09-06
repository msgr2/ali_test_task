<?php

namespace App\Services;

use App\Models\Country;
use Carbon\Carbon;
use DateTimeZone;
use Exception;
use Nnjeim\World\World;

class CountryService
{
    public static function guessCountry($countryName): int|false
    {
        $countryName = strtolower(trim($countryName, "_-\t\n \r\0\"'"));
        if (empty($countryName)) {
            return false;
        }

        if ($countryName == 'uk') {
            $countryName = 'gb';
        }

        if (is_numeric($countryName)) {
            $country = Country::where(['id' => $countryName])->first();

            if (!empty($country)) {
                return $country->id;
            }
        }

        if (strlen($countryName) === 2) {
            $country = Country::where(['iso' => strtoupper($countryName)])->first();

            if (!empty($country)) {
                return $country->id;
            }
        }

        if (strlen($countryName) === 3) {
            $country = Country::where(['iso3' => strtoupper($countryName)])->first();

            if (!empty($country)) {
                return $country->id;
            }
        }

        $country = Country::where(['nicename' => ucwords($countryName)])->first();

        if (!empty($country)) {
            return $country->id;
        }

        throw new Exception("Country [{$countryName}] not found");
    }

    public static function timeHasPassed(string $timezone, string $time): bool
    {
        return Carbon::parse($time, new DateTimeZone($timezone))->isPast();
    }

}
