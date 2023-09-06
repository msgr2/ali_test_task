<?php

namespace App\Services;

use App\Models\Team;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DomainRegisterService
{
    public static function getTldList(Team $team)
    {
        $command = 'namecheap.domains.getTldList';

        if ($cached = Cache::get($command)) {
            return json_decode($cached, true);
        }

        try {
            $url = self::buildUrl($team, $command);
            $response = Http::get($url);
            $xml = new \SimpleXMLElement($response->body());

            $errors = self::getErrors($xml);
            $tlds = [];

            if (empty($errors)) {
                foreach ($xml->CommandResponse->Tlds->Tld as $tld) {
                    if ($tld['IsApiRegisterable'] == 'false') {
                        continue;
                    }

                    $tlds[] = [
                        'name' => (string)$tld['Name'],
                        'min_years' => (int)$tld['MinRegisterYears'],
                        'max_years' => (int)$tld['MaxRegisterYears'],
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Unable to get TLD list from Namecheap', [
                'team_id' => $team->id,
                'command' => $command,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'errors' => [
                    [
                        'number' => 0,
                        'message' => 'Unable to get TLD list from Namecheap',
                    ]
                ],
                'tlds' => [],
            ];
        }

        $result = [
            'errors' => $errors,
            'tlds' => $tlds,
        ];

        Cache::set($command, json_encode($result), 60 * 60 * 24);

        return $result;
    }

    public static function createDomain(Team $team, string $domainName, int $years = 1): array
    {
        $data = $team->meta['domain_register'] ?? [];
        $firstName = $data['first_name'] ?? null;
        $lastName = $data['last_name'] ?? null;
        $address1 = $data['address1'] ?? null;
        $address2 = $data['address2'] ?? null;
        $city = $data['city'] ?? null;
        $stateProvince = $data['state_province'] ?? null;
        $postalCode = $data['postal_code'] ?? null;
        $country = $data['country'] ?? null;
        $phone = $data['phone'] ?? null;
        $email = $data['email'] ?? null;

        $params = [
            'DomainName' => $domainName,
            'Years' => $years,
            'RegistrantFirstName' => $firstName,
            'RegistrantLastName' => $lastName,
            'RegistrantAddress1' => $address1,
            'RegistrantAddress2' => $address2,
            'RegistrantCity' => $city,
            'RegistrantStateProvince' => $stateProvince,
            'RegistrantPostalCode' => $postalCode,
            'RegistrantCountry' => $country,
            'RegistrantPhone' => $phone,
            'RegistrantEmailAddress' => $email,
            'TechFirstName' => $data['tech_first_name'] ?? $firstName,
            'TechLastName' => $data['tech_last_name'] ?? $lastName,
            'TechAddress1' => $data['tech_address1'] ?? $address1,
            'TechAddress2' => $data['tech_address2'] ?? $address2,
            'TechCity' => $data['tech_city'] ?? $city,
            'TechStateProvince' => $data['tech_state_province'] ?? $stateProvince,
            'TechPostalCode' => $data['tech_postal_code'] ?? $postalCode,
            'TechCountry' => $data['tech_country'] ?? $country,
            'TechPhone' => $data['tech_phone'] ?? $phone,
            'TechEmailAddress' => $data['tech_email_address'] ?? $email,
            'AdminFirstName' => $data['admin_first_name'] ?? $firstName,
            'AdminLastName' => $data['admin_last_name'] ?? $lastName,
            'AdminAddress1' => $data['admin_address1'] ?? $address1,
            'AdminAddress2' => $data['admin_address2'] ?? $address2,
            'AdminCity' => $data['admin_city'] ?? $city,
            'AdminStateProvince' => $data['admin_state_province'] ?? $stateProvince,
            'AdminPostalCode' => $data['admin_postal_code'] ?? $postalCode,
            'AdminCountry' => $data['admin_country'] ?? $country,
            'AdminPhone' => $data['admin_phone'] ?? $phone,
            'AdminEmailAddress' => $data['admin_email_address'] ?? $email,
            'AuxBillingFirstName' => $data['aux_billing_first_name'] ?? $firstName,
            'AuxBillingLastName' => $data['aux_billing_last_name'] ?? $lastName,
            'AuxBillingAddress1' => $data['aux_billing_address1'] ?? $address1,
            'AuxBillingAddress2' => $data['aux_billing_address2'] ?? $address2,
            'AuxBillingCity' => $data['aux_billing_city'] ?? $city,
            'AuxBillingStateProvince' => $data['aux_billing_state_province'] ?? $stateProvince,
            'AuxBillingPostalCode' => $data['aux_billing_postal_code'] ?? $postalCode,
            'AuxBillingCountry' => $data['aux_billing_country'] ?? $country,
            'AuxBillingPhone' => $data['aux_billing_phone'] ?? $phone,
            'AuxBillingEmailAddress' => $data['aux_billing_email_address'] ?? $email,
        ];

        $result = null;

        try {
            $url = self::buildUrl($team, 'namecheap.domains.create', $params);

            $response = Http::post($url);

            Log::info('Create domain response', [
                'team_id' => $team->id,
                'domain_name' => $domainName,
                'response' => $response->body(),
            ]);

            $xml = new \SimpleXMLElement($response->body());
            $errors = self::getErrors($xml);

            if (empty($errors)) {
                $result = [
                    'domain' => (string)$xml->CommandResponse->DomainCreateResult->attributes()['Domain'],
                    'charged_amount' => (string)$xml->CommandResponse->DomainCreateResult->attributes()['ChargedAmount'],
                    'domain_id' => (string)$xml->CommandResponse->DomainCreateResult->attributes()['DomainID'],
                    'transaction_id' => (string)$xml->CommandResponse->DomainCreateResult->attributes()['TransactionID'],
                    'order_id' => (string)$xml->CommandResponse->DomainCreateResult->attributes()['OrderID'],
                ];
            }
        } catch (\Exception $e) {
            Log::error('Unable to create domain', [
                'team_id' => $team->id,
                'domain_name' => $domainName,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $errors = [
                [
                    'number' => 0,
                    'message' => $e->getMessage(),
                ]
            ];
        }

        return [
            'errors' => $errors,
            'result' => $result,
        ];
    }

    private static function getErrors($xml): array
    {
        $errors = [];
        foreach ($xml->Errors->Error as $error) {
            $errors[] = [
                'number' => (string)$error['Number'],
                'message' => (string)$error,
            ];
        }
        return $errors;
    }

    private static function buildUrl(Team $team, string $command, array $extraParams = []): string
    {
        $data = $team->meta['domain_register'] ?? [];

        $authParams = [
            'ApiUser' => $data['api_user'] ?? null,
            'ApiKey' => $data['api_key'] ?? null,
            'UserName' => $data['api_user'] ?? null,
            'ClientIp' => $data['client_ip'] ?? null,
        ];

        return config('services.namecheap.api_url') . '?' . http_build_query(
                array_merge(
                    $authParams,
                    ['Command' => $command],
                    $extraParams,
                )
            );
    }
}
