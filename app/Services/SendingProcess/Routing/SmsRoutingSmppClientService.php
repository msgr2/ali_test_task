<?php


namespace App\Services\SendingProcess\Routing;

use App\Data\SmppConnectionData;
use App\Models\SmsRouteSmppConnection;
use App\Services\SendingProcess\Telecom\Encoder\GsmEncoder;
use App\Services\SendingProcess\Telecom\SMPP\Exception\SmppException;
use App\Services\SendingProcess\Telecom\SMPP\SMPP;
use App\Services\SendingProcess\Telecom\SMPP\SmppAddress;
use App\Services\SendingProcess\Telecom\SMPP\SmppClient;
use App\Services\SendingProcess\Telecom\SMPP\SmppTag;
use App\Services\SendingProcess\Telecom\SMPP\Unit\SmppDeliveryReceipt;
use App\Services\SendingProcess\Telecom\SMPP\Unit\SmppPdu;
use App\Services\SendingProcess\Telecom\SMPP\Unit\SmppSms;
use App\Services\SendingProcess\Telecom\Transport\SocketTransport;
use DateInterval;
use DateTime;
use Exception;
use InvalidArgumentException;
use Log;
use RuntimeException;

/**
 * Class for receiving or sending sms through SMPP protocol.
 * This is a reduced implementation of the SMPP protocol, and as such not all features will or ought to be available.
 * The purpose is to create a lightweight and simplified SMPP client.
 *
 * @author hd@onlinecity.dk, paladin
 * @see http://en.wikipedia.org/wiki/Short_message_peer-to-peer_protocol - SMPP 3.4 protocol specification
 * Derived from work done by paladin, see: http://sourceforge.net/projects/phpsmppapi/
 *
 * Copyright (C) 2011 OnlineCity
 * Copyright (C) 2006 Paladin
 *
 * This library is free software; you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Lesser General Public License for more details.
 *
 * This license can be read at: http://www.opensource.org/licenses/lgpl-2.1.php
 */
class SmsRoutingSmppClientService
{
    public SmppClient $smppClient;
    private $isDemo = false; //for testing.

    public static function createSmppClient(SmsRouteSmppConnection $smppConnectionData): SmsRoutingSmppClientService
    {

        $transport = new SocketTransport(array($smppConnectionData->url),
            $smppConnectionData->port,
            $persist = true,
            function ($message) {
                Log::debug("SMPP transport debug: {$message}");
            });

        $transport->setRecvTimeout(10000);
        $transport->setSendTimeout(10000);

        SmppClient::$sms_null_terminate_octetstrings = false;
        SmppClient::$csms_method = SmppClient::CSMS_PAYLOAD;
        SmppClient::$system_type = '';
        SmppClient::$sms_registered_delivery_flag = SMPP::REG_DELIVERY_SMSC_BOTH;
        SmppClient::$system_type = '';

        $smpp = new SmppClient($transport, function ($message) {
            Log::debug("SMPP debug: {$message}");
        });
        $smpp->debug = true;
        $transport->debug = true;
        try {
            $transport->open();
            $smpp->bindTransceiver($smppConnectionData->username, $smppConnectionData->password);
//            } elseif ($receiver) {
//                $smpp->bindReceiver($username, $password); //in the future if we would like to receive DLR's
//            } else {
//                $smpp->bindTransmitter($username, $password);
//            }
        } catch (Exception $e) {
            Log::warning("SMPP connection error: {$e->getMessage()}", ['smppConnectionData' => $smppConnectionData]);
            throw new Exception("SMPP connection error: {$e->getMessage()}", $e->getCode());
        }
        $service = new SmsRoutingSmppClientService();
        $service->smppClient = $smpp;


        if ($smppConnectionData->url === '167.235.66.91') {
            $service->isDemo = true;
        }
        return $service;
    }

    public function sendSms($from, $to, $message, $isUnicode, $isLong): bool|SmppPdu|array
    {
        SmppClient::$sms_null_terminate_octetstrings = false;
        SmppClient::$csms_method = $isLong ? SmppClient::CSMS_8BIT_UDH : SmppClient::CSMS_PAYLOAD;
        SmppClient::$sms_registered_delivery_flag = SMPP::REG_DELIVERY_SMSC_BOTH;

        if ($isUnicode) {
//            $dataCoding = SMPP::DATA_CODING_ISO8859_1;
//            if (self::isSpanish($message)) {
//                $message    = utf8_decode($message);
//            }else{
            $message = mb_convert_encoding($message, 'UCS-2', 'UTF-8');
            $dataCoding = SMPP::DATA_CODING_UCS2;
//            }
        } else {
            $message = GsmEncoder::utf8_to_gsm0338($message);
            $dataCoding = SMPP::DATA_CODING_DEFAULT;
        }

        $ton = SMPP::TON_ALPHANUMERIC;

        $from = new SmppAddress($from, $ton);
        $to = new SmppAddress($to, SMPP::TON_INTERNATIONAL, SMPP::NPI_E164);

        try {
            return $this->smppClient->sendSMS($from, $to, $message, null, $dataCoding);
        } catch (SmppException $e) {
            Log::error('SMPP exception', ['msg' => $message, 'e' => $e->getMessage()]);
            return false;
        } catch (Exception $e) {
            Log::error('General exception', ['e' => $e->getMessage()]);

            if (str_contains($e->getMessage(), 'unable to write to socket')) {
                try {
                    $this->smppClient->reconnect();
                    return $this->smppClient->sendSMS($from, $to, $message, null, $dataCoding);
                } catch (Exception $ee) {
                    Log::error('Failed to reconnect', ['e' => $e->getMessage()]);
                    return false;
                }
            }
        }

        return false;
    }

    public function syncSmppDlrs()
    {
        //todo: add cron to sync all dlr's every 1 hours from all providers.. even if we don't have a running sending
        // with them.
        Log::debug('Syncing smpp dlrs');
        if ($this->isDemo) {
            Log::debug('Skipping smpp dlr sync for testing');
            return;
        }
        try {
            while ($dlr = $this->smppClient->readSMS()) {
                if (!($dlr instanceof SmppDeliveryReceipt)) {
                    $this->handleNonDlrMessage($dlr);
                    continue;
                }

                $normalized = $dlr->source->value;
                $this->handleSmsDlr($dlr, $normalized);
            }

            $this->smppClient->enquireLink();
            $this->checkRunDuration();
        } catch (Exception $e) {
            $this->handleDlrSyncException($e);
        }
    }

    protected function handleNonDlrMessage($dlr, $gateway_id)
    {
        if (($dlr instanceof SmppSms) && !empty($dlr->source->value) && !empty($dlr->destination->value)) {
            Log::warning('Received an inbound sms but it is not implemented yet', ['dlr' => $dlr]);
//            $this->_inboundMessage($dlr, $gateway_id); // look at v1
        } else {
            Log::warning('Not a DLR Message: ' . print_r($dlr, true));
        }
    }

    protected function handleSmsDlr(SmppDeliveryReceipt $dlr, $normalized, $gateway_id, $transceiver)
    {
        return false;
        //todo
        /** @var SendLog $sl */
        #get the sms id from the sms send log
//        $dlr->id;
//        $sl = $this->findSendLog($dlr, $gateway_id);

        # If network info exists in the DLR, we need to update the network info in the send log
        $this->handleDlrTags($dlr, $sl);

        $ok = trim($dlr->message);
        $msg = 'DLR completed';

        if (!empty($ok)) {
            if (!$sl) {
                $this->createAndSaveSendLogMeta($dlr, $normalized, $gateway_id, $msg);
            } else {
                $sl->saveDlr($dlr);
            }
        } else {
            $msg .= ' - Empty Message [' .
                print_r($dlr, true) .
                '] [' .
                $normalized .
                ' / ' .
                $gateway_id .
                ']';
        }

        if ($transceiver) {
            return true;
        }
    }

    private function handleDlrTags(SmppDeliveryReceipt $dlr, SendLog $sl, $gateway_id)
    {
        //todo: add mccmnc to numbers_networks if returned in dlr
        if (!empty($dlr->tags)) {
            $mid = null;
            foreach ($dlr->tags as $t) {
                if (empty($sl) && dechex($t->id) == dechex(SmppTag::RECEIPTED_MESSAGE_ID)) {
                    $mid = trim($t->value);
                }
                if ((dechex($t->id) == dechex(SmppTag::MCCMNC) ||
                        dechex($t->id) == dechex(SmppTag::MCCMNC2)) && strlen($t->value) <= 6) {
                    echo 'MCCMNC [' .
                        $dlr->id .
                        '] [' .
                        $dlr->source->value .
                        '] [' .
                        $t->value .
                        ']' .
                        "\n";
//                    NumbersNetwork::add($dlr->source->value, $t->value);
                }
            }
            if (empty($sl) && !empty($mid)) {
//                $sl = SendLog::find()->where(['foreign_id' => trim($mid)])->andWhere('gateway_id = ' .
//                    $gateway_id .
//                    ' OR gateway_id_real = ' .
//                    $gateway_id .
//                    ' OR gateway_id_test = ' .
//                    $gateway_id)->one();
            }
        }
    }

    protected function checkRunDuration()
    {
        $max_uptime = 30;
        $startTime = $this->getStartTime();
        $endTime = microtime(true);
        $deltaTime = floor(($endTime - $startTime) / 60); // in minutes
        if ($deltaTime >= $max_uptime) {
            $exitMsg = "DLR run for $deltaTime, resetting ...";
            Log::debug('DLR run for ' . $deltaTime . ' minutes, resetting ...');

            exit($exitMsg);
        }
    }

    private function getStartTime()
    {
        if (empty($this->startTime)) {
            $this->startTime = microtime(true);
        }

        return $this->startTime;
    }

    protected function handleDlrSyncException($e)
    {
        if (str_contains($e->getMessage(), 'Timed out waiting for data on socket')) {
            Log::warning($e->getMessage());
        } elseif (str_contains($e->getMessage(), 'Could not connect to any of the specified hosts')) {
            Log::warning($e->getMessage());
        } elseif (str_contains($e->getMessage(), 'Could not parse delivery receipt')) {
            Log::warning($e->getMessage());
        } else {
            throw $e;
        }
    }

    protected function handleHlrDlr($dlr, $normalized)
    {
        // Implementation of the HLR DLR handling code goes here
    }

    protected function getSmppClientInstance()
    {
        return $this->smppClient;
    }
}
