<?php

namespace App\Services;

use App\Models\MobileNetwork;

class SmsContactMobileNetworksService
{
    public static function getNetworks($contacts)
    {
        $contacts->each(function ($contact) {
            $contact->network = self::getNetwork($contact->phone_normalized);
        });
    }

    private static function getNetwork(int $phone_normalized)
    {
        ClickhouseService::getClient()->select('
            SELECT
                network
            FROM
                phone_networks
            WHERE
                phone = :phone
            LIMIT 1)',
            ['phone' => $phone_normalized]);
    }

    public static function saveNumberNetwork(int $phone_normalized, int $network_id): void
    {
        $network = MobileNetwork::find($network_id);
        if (empty($network->brand)) {
            $network->brand = 'Unknown brand';
        }
        ClickhouseService::getClient()
            ->write("INSERT INTO v2_numbers_networks (normalized, network_id, network_brand) 
                            VALUES (:phone, :network_id, :brand)",
                [
                    'phone' => $phone_normalized,
                    'network_id' => $network_id,
                    'brand' => $network->brand,
                ]);
    }

    public static function getNetworkCacheForNumber($phone_normalized)
    {
        $res = ClickhouseService::getClient()->select('
        select 
            network_id
            from 
                v2_numbers_networks
            where
                normalized = :phone
        ',
            ['phone' => $phone_normalized])->rows();
        if (empty($res)) {
            return false;
        }

        return $res[0]['network_id'];
    }

    public static function getContactsForNetworkByList($list_id)
    {
        return ClickhouseService::getClient()->write("
select team_id,
       list_id,
       phone_normalized,
       anyLast(last_sent)     as last_sent,
       anyLast(last_clicked)  as last_clicked,
       anyLast(network_id)    as network_id,
       anyLast(network_brand) as network_brand
from contacts_sms_materialized
where list_id = :list_id
group by phone_normalized, list_id, team_id",
            [
                'list_id' => $list_id,
            ])->rows();
    }

    public static function getNetworksCountByList($list_id)
    {
        return ClickhouseService::getClient()->select("
select network_brand,
       anyLast(network_id) as sample_network_id,
       anyLast(phone_normalized) as sample_phone_normalized,
         count() as count
from ( 
select team_id, list_id, 
    phone_normalized, anyLast(network_brand) as network_brand, anyLast(network_id) as network_id from contacts_sms_materialized
where list_id = :list_id
group by phone_normalized, list_id, team_id
) a1
where list_id = :list_id
group by network_brand
order by count desc
",
            [
                'list_id' => $list_id,
            ])->rows();
    }
}
