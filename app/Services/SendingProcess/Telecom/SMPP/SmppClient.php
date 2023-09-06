<?php

namespace App\Services\SendingProcess\Telecom\SMPP;

use App\Models\SmsRouteSmppConnection;
use backend\models\GatewaysErrors;
use App\Services\SendingProcess\Telecom\SMPP\Exception\SmppException;
use App\Services\SendingProcess\Telecom\SMPP\Unit\SmppDeliveryReceipt;
use App\Services\SendingProcess\Telecom\SMPP\Unit\SmppPdu;
use App\Services\SendingProcess\Telecom\SMPP\Unit\SmppSms;
use App\Services\SendingProcess\Telecom\Transport\SocketTransport;
use DateInterval;
use DateTime;
use Exception;
use InvalidArgumentException;
use RuntimeException;

//use VmgLtd\HlrLookupClient;

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
class SmppClient
{
    // SMPP bind parameters
    /**
     * Use sar_msg_ref_num and sar_total_segments with 16 bit tags
     * @var integer
     */
    const CSMS_16BIT_TAGS = 0;
    /**
     * Use message payload for CSMS
     * @var integer
     */
    const CSMS_PAYLOAD = 1;
    /**
     * Embed a UDH in the message with 8-bit reference.
     * @var integer
     */
    const CSMS_8BIT_UDH = 2;
    const MAX_RETRIES = 3;
    public static $system_type = "WWW";

    // ESME transmitter parameters
    public static $interface_version = 0x34;
    public static $addr_ton = 0;
    public static $addr_npi = 0;
    public static $address_range = "";
    public static $sms_service_type = "";
    public static $sms_esm_class = 0x00;
    public static $sms_protocol_id = 0x00;
    public static $sms_priority_flag = 0x00;
    public static $sms_registered_delivery_flag = 0x00;
    public static $sms_replace_if_present_flag = 0x00;
    public static $sms_sm_default_msg_id = 0x00;
    /**
     * SMPP v3.4 says octect string are "not necessarily NULL terminated".
     * Switch to toggle this feature
     * @var boolean
     */
    public static $sms_null_terminate_octetstrings = true; //for send again attempts in case of catch exception
    public static $csms_method = SmppClient::CSMS_16BIT_TAGS;
    /**
     * @var int for submit_sm retries
     */
    public static $retries = 1;
    public $debug;
    public $gtw_type;
    protected $pdu_queue;

    // Used for reconnect
    protected $debugHandler;
    protected $mode;
    protected $sequence_number;
    protected $sar_msg_ref_num;
    protected $last_enquire_link;
    private $login;
    private $pass;

    /**
     * Construct the SMPP class
     *
     * @param string $debugHandler
     */
    public function __construct(protected SocketTransport $transport, $debugHandler = null)
    {
        // Internal parameters
        $this->sequence_number = 1;
        $this->debug = false;
        $this->pdu_queue = array();
        $this->debugHandler = $debugHandler ? $debugHandler : 'error_log';
        $this->mode = null;
    }

    public static function testConnection(SmsRouteSmppConnection $make)
    {
        return true; //todo
    }

    /**
     * Binds the receiver. One object can be bound only as receiver or only as trancmitter.
     * @param string $login - ESME system_id
     * @param string $pass - ESME password
     * @throws SmppException
     */
    public function bindReceiver($login, $pass)
    {
        if (!$this->transport->isOpen()) return false;
        if ($this->debug) call_user_func($this->debugHandler, 'Binding receiver...');

        $response = $this->_bind($login, $pass, SMPP::BIND_RECEIVER);

        if ($this->debug) call_user_func($this->debugHandler,
            "Binding status  : " . (!empty($response['response']) ? $response['response']->status : 'null'));
        $this->mode = 'receiver';
        $this->login = $login;
        $this->pass = $pass;
        $this->last_enquire_link = time();
    }

    /**
     * Binds the socket and opens the session on SMSC
     * @param string $login - ESME system_id
     * @param string $pass - ESME password
     * @param        $command_id
     * @return SmppPdu
     */
    protected function _bind($login, $pass, $command_id)
    {
        // Make PDU body
        $pduBody = pack(
            'a' . (strlen($login) + 1) .
            'a' . (strlen($pass) + 1) .
            'a' . (strlen(self::$system_type) + 1) .
            'CCCa' . (strlen(self::$address_range) + 1),
            $login,
            $pass,
            self::$system_type,
            self::$interface_version,
            self::$addr_ton,
            self::$addr_npi,
            self::$address_range
        );
        $response = $this->sendCommand($command_id, $pduBody);

        return $response;
    }

    /**
     * Sends the PDU command to the SMSC and waits for response.
     * @param integer $id - command ID
     * @param string $pduBody - PDU body
     */
    protected function sendCommand($id, $pduBody): SmppPdu|array
    {
        $pdu = new SmppPdu($id, 0, $this->sequence_number, $pduBody);

        if (!$this->transport->isOpen()) {
            $msg = 'Closed connection on command: 0x' . dechex($id) . "\n";
            $msg .= 'Incoming PDU:' . "\n" . $pdu->getReadPduOutput();
            throw new SmppException($msg, GatewaysErrors::GATEWAY_ERROR_CONNECTION);
        }

        $sentPduOutput = $this->sendPDU($pdu);
        /** @var SmppPdu $response */
        $response = $this->readPDU_resp($this->sequence_number, $pdu->id);
        if ($response === false) {
            $msg = 'Failed to read reply to command: 0x' . dechex($id) . "\n";
            $msg .= "sent PDU output:\n $sentPduOutput";
            throw new SmppException($msg, GatewaysErrors::GATEWAY_ERROR_READ_REPLY);
        }

        $this->sequence_number++;

        if ($response->status != SMPP::ESME_ROK) {
            $msg = <<<STR
                Sent PDU output:
                $sentPduOutput
                
                Read PDU output:
                {$response->getReadPduOutput()}
STR;
            $msg .= "\nresponse status: " . SMPP::getStatusMessage($response->status);
            throw new SmppException($msg, $response->status);
        }

        // Reached max sequence number, spec does not state what happens now, so we re-connect
        if ($this->sequence_number >= 0x7FFFFFFF) {
            $this->reconnect();
        }

        return ['response' => $response, 'sent' => $sentPduOutput, 'read' => $response->getReadPduOutput()];
    }

    /**
     * Prepares and sends PDU to SMSC.
     *
     * @return string
     */
    protected function sendPDU(SmppPdu $pdu)
    {
        $length = strlen($pdu->body) + 16;
        $header = pack("NNNN", $length, $pdu->id, $pdu->status, $pdu->sequence);
        if ($this->debug) {
            call_user_func($this->debugHandler, "Send PDU         : $length bytes");
            call_user_func($this->debugHandler, ' ' . chunk_split(bin2hex($header . $pdu->body), 2, " "));
            call_user_func($this->debugHandler, ' command_id      : 0x' . dechex($pdu->id));
            call_user_func($this->debugHandler, ' sequence number : ' . $pdu->sequence);
        }
        $this->transport->write($header . $pdu->body, $length);

        $msg = "Send PDU         : $length bytes\n";
        $msg .= ' ' . chunk_split(bin2hex($header . $pdu->body), 2, " ") . "\n";
        $msg .= ' command_id      : 0x' . dechex($pdu->id) . "\n";
        $msg .= ' sequence number : ' . $pdu->sequence . "\n";

        return $msg;

    }

    /**
     * Waits for SMSC response on specific PDU.
     * If a GENERIC_NACK with a matching sequence number, or null sequence is received instead it's also accepted.
     * Some SMPP servers, ie. logica returns GENERIC_NACK on errors.
     *
     * @param integer $seq_number - PDU sequence number
     * @param integer $command_id - PDU command ID
     * @throws SmppException
     */
    protected function readPDU_resp($seq_number, $command_id): SmppPdu|bool
    {
        // Get response cmd id from command id
        $command_id = $command_id | SMPP::GENERIC_NACK;

        // Check the queue first
        $ql = count($this->pdu_queue);
        for ($i = 0; $i < $ql; $i++) {
            $pdu = $this->pdu_queue[$i];
            if (
                ($pdu->sequence == $seq_number && ($pdu->id == $command_id || $pdu->id == SMPP::GENERIC_NACK)) ||
                ($pdu->sequence == null && $pdu->id == SMPP::GENERIC_NACK)
            ) {
                // remove response pdu from queue
                array_splice($this->pdu_queue, $i, 1);
                return $pdu;
            }
        }

        // Read PDUs until the one we are looking for shows up, or a generic nack pdu with matching sequence or null sequence
        do {
            $pdu = $this->readPDU();
            if ($pdu == false) {
                return false;
            }

            if ($pdu->id == SMPP::ENQUIRE_LINK) {
                $response = new SmppPdu(SMPP::ENQUIRE_LINK_RESP, SMPP::ESME_ROK, $pdu->sequence, "");
                $this->sendPDU($response);
            }
            if ($pdu->sequence == $seq_number && ($pdu->id == $command_id || $pdu->id == SMPP::GENERIC_NACK)) {
                return $pdu;
            }
            if ($pdu->sequence == null && $pdu->id == SMPP::GENERIC_NACK) {
                return $pdu;
            }
            array_push($this->pdu_queue, $pdu); // unknown PDU push to queue
        } while ($pdu);

        return false;
    }

    /**
     * Reads incoming PDU from SMSC.
     * @return SmppPdu
     */
    protected function readPDU()
    {
        // Read PDU length
        $bufLength = $this->transport->read(4);
        if (!$bufLength) return false;
        extract(unpack("Nlength", $bufLength));

        // Read PDU headers
        $bufHeaders = $this->transport->read(12);
        if (!$bufHeaders) return false;
        extract(unpack("Ncommand_id/Ncommand_status/Nsequence_number", $bufHeaders));
        // Read PDU body
        if ($length - 16 > 0) {
            $body = $this->transport->readAll($length - 16);
            if (!$body) throw new RuntimeException("Could not read PDU body, sequence_number: $sequence_number",
                GatewaysErrors::GATEWAY_ERROR_READ_PDU);
        } else {
            $body = null;
        }

        if ($this->debug) {
            call_user_func($this->debugHandler, "Read PDU         : $length bytes");
            call_user_func($this->debugHandler, ' ' . chunk_split(bin2hex($bufLength . $bufHeaders . $body), 2, " "));
            call_user_func($this->debugHandler, " command id      : 0x" . dechex($command_id));
            call_user_func($this->debugHandler,
                " command status  : 0x" . dechex($command_status) . " " . SMPP::getStatusMessage($command_status));
            call_user_func($this->debugHandler, ' sequence number : ' . $sequence_number);
        }
        return new SmppPdu($command_id,
            $command_status,
            $sequence_number,
            $body,
            $length,
            $bufLength,
            $bufHeaders,
            $command_id);
    }

    /**
     * Reconnect to SMSC.
     * This is mostly to deal with the situation were we run out of sequence numbers
     */
    public function reconnect()
    {
        $this->close();
        sleep(1);
        $this->transport->open();
        $this->sequence_number = 1;

        if ($this->mode == 'receiver') {
            $this->bindReceiver($this->login, $this->pass);
        } else {
            $this->bindTransmitter($this->login, $this->pass);
        }
    }

    /**
     * Closes the session on the SMSC server.
     */
    public function close($unbind = true)
    {
        if (!$this->transport->isOpen()) return;

        if ($unbind) {

            if ($this->debug) call_user_func($this->debugHandler, 'Unbinding...');

            try {
                $response = $this->sendCommand(SMPP::UNBIND, "");
            } catch (Exception $e) {
                echo "\n" . 'UNBIND Exception: [' . $e->getMessage() . ']' . "\n";
            }

            if ($this->debug) call_user_func($this->debugHandler,
                "Unbind status   : " . (!empty($response['response']) ? $response['response']->status : 'null'));
        }

        $this->transport->close();
    }

    /**
     * Binds the transmitter. One object can be bound only as receiver or only as transmitter.
     * @param string $login - ESME system_id
     * @param string $pass - ESME password
     *
     * @return bool
     * @throws SmppException
     */
    public function bindTransmitter($login, $pass)
    {
        if (!$this->transport->isOpen()) return false;
        if ($this->debug) call_user_func($this->debugHandler, 'Binding transmitter...');

        $response = $this->_bind($login, $pass, SMPP::BIND_TRANSMITTER);

        if ($this->debug) call_user_func($this->debugHandler,
            "Binding status  : " . (!empty($response['response']) ? $response['response']->status : 'null'));
        $this->mode = 'transmitter';
        $this->login = $login;
        $this->pass = $pass;
        $this->last_enquire_link = time();
    }

    public function bindTransceiver($login, $pass)
    {
        if (!$this->transport->isOpen()) return false;
        if ($this->debug) call_user_func($this->debugHandler, 'Binding transceiver...');

        $response = $this->_bind($login, $pass, SMPP::BIND_TRANSCEIVER);

        if ($this->debug) call_user_func($this->debugHandler,
            "Binding status  : " . (!empty($response['response']) ? $response['response']->status : 'null'));
        $this->mode = 'transceiver';
        $this->login = $login;
        $this->pass = $pass;
        $this->last_enquire_link = time();
    }

    /**
     * Query the SMSC about current state/status of a previous sent SMS.
     * You must specify the SMSC assigned message id and source of the sent SMS.
     * Returns an associative array with elements: message_id, final_date, message_state and error_code.
     *    message_state would be one of the SMPP::STATE_* constants. (SMPP v3.4 section 5.2.28)
     *    error_code depends on the telco network, so could be anything.
     *
     * @param string $messageid
     * @return array
     */
    public function queryStatus($messageid, SmppAddress $source)
    {
        $pduBody = pack('a' . (strlen($messageid) + 1) . 'cca' . (strlen($source->value) + 1),
            $messageid,
            $source->ton,
            $source->npi,
            $source->value);
        $reply = $this->sendCommand(SMPP::QUERY_SM, $pduBody);
        if (!$reply || $reply->status != SMPP::ESME_ROK) return null;

        // Parse reply
        $posId = strpos($reply->body, "\0", 0);
        $posDate = strpos($reply->body, "\0", $posId + 1);
        $data = array();
        $data['message_id'] = substr($reply->body, 0, $posId);
        $data['final_date'] = substr($reply->body, $posId, $posDate - $posId);
        $data['final_date'] = $data['final_date'] ? $this->parseSmppTime(trim($data['final_date'])) : null;
        $status = unpack("cmessage_state/cerror_code", substr($reply->body, $posDate + 1));
        return array_merge($data, $status);
    }

    /**
     * Parse a timestring as formatted by SMPP v3.4 section 7.1.
     * Returns an unix timestamp if $newDates is false or DateTime/DateInterval is missing,
     * otherwise an object of either DateTime or DateInterval is returned.
     *
     * @param string $input
     * @param boolean $newDates
     * @return mixed
     */
    public function parseSmppTime($input, $newDates = true)
    {
        // Check for support for new date classes
        if (!class_exists('DateTime') || !class_exists('DateInterval')) $newDates = false;

        $numMatch =
            preg_match('/^(\\d{2})(\\d{2})(\\d{2})(\\d{2})(\\d{2})(\\d{2})(\\d{1})(\\d{2})([R+-])$/', $input, $matches);
        if (!$numMatch) return null;
        list($whole, $y, $m, $d, $h, $i, $s, $t, $n, $p) = $matches;

        // Use strtotime to convert relative time into a unix timestamp
        if ($p == 'R') {
            if ($newDates) {
                $spec = "P";
                if ($y) $spec .= $y . 'Y';
                if ($m) $spec .= $m . 'M';
                if ($d) $spec .= $d . 'D';
                if ($h || $i || $s) $spec .= 'T';
                if ($h) $spec .= $h . 'H';
                if ($i) $spec .= $i . 'M';
                if ($s) $spec .= $s . 'S';
                return new DateInterval($spec);
            } else {
                return strtotime("+$y year +$m month +$d day +$h hour +$i minute $s +second");
            }
        } else {
            $offsetHours = floor($n / 4);
            $offsetMinutes = ($n % 4) * 15;
            $time = sprintf("20%02s-%02s-%02sT%02s:%02s:%02s%s%02s:%02s",
                $y,
                $m,
                $d,
                $h,
                $i,
                $s,
                $p,
                $offsetHours,
                $offsetMinutes); // Not Y3K safe
            if ($newDates) {
                return new DateTime($time);
            } else {
                return strtotime($time);
            }
        }
    }

    /**
     * Read one SMS from SMSC. Can be executed only after bindReceiver() call.
     * This method bloks. Method returns on socket timeout or enquire_link signal from SMSC.
     * @return sms associative array or false when reading failed or no more sms.
     */
    public function readSMS()
    {
        $command_id = SMPP::DELIVER_SM;
        // Check the queue
        $ql = count($this->pdu_queue);
        for ($i = 0; $i < $ql; $i++) {
            $pdu = $this->pdu_queue[$i];
            if ($pdu->id == $command_id) {
                //remove response
                array_splice($this->pdu_queue, $i, 1);
                return $this->parseSMS($pdu);
            }
        }
        // Read pdu
        do {
            $pdu = $this->readPDU();
            if ($pdu === false) return false; // TSocket v. 0.6.0+ returns false on timeout
            //check for enquire link command
            if ($pdu->id == SMPP::ENQUIRE_LINK) {
                $response = new SmppPdu(SMPP::ENQUIRE_LINK_RESP, SMPP::ESME_ROK, $pdu->sequence, "");
                $this->sendPDU($response);
            } elseif ($pdu->id == SMPP::UNBIND) {
                $response = new SmppPdu(SMPP::UNBIND_RESP, SMPP::ESME_ROK, $pdu->sequence, "");
                $this->sendPDU($response);
                $this->close(false);
            } else if ($pdu->id != $command_id) { // if this is not the correct PDU add to queue
                array_push($this->pdu_queue, $pdu);
            }
        } while ($pdu && $pdu->id != $command_id);

        if ($pdu) return $this->parseSMS($pdu);
        return false;
    }

    /**
     * Parse received PDU from SMSC.
     * @param SmppPdu $pdu - received PDU from SMSC.
     * @return parsed PDU as array.
     */
    protected function parseSMS(SmppPdu $pdu)
    {
        // Check command id
        if ($pdu->id != SMPP::DELIVER_SM) throw new InvalidArgumentException('PDU is not an received SMS',
            GatewaysErrors::GATEWAY_ERROR_NOT_DLR);

        // Unpack PDU
        $ar = unpack("C*", $pdu->body);

        // Read mandatory params
        $service_type = $this->getString($ar, 6, true);

        $source_addr_ton = next($ar);
        $source_addr_npi = next($ar);
        $source_addr = $this->getString($ar, 21);
        $source = new SmppAddress($source_addr, $source_addr_ton, $source_addr_npi);

        $dest_addr_ton = next($ar);
        $dest_addr_npi = next($ar);
        $destination_addr = $this->getString($ar, 21);
        $destination = new SmppAddress($destination_addr, $dest_addr_ton, $dest_addr_npi);

        $esmClass = next($ar);
        $protocolId = next($ar);
        $priorityFlag = next($ar);
        next($ar); // schedule_delivery_time
        next($ar); // validity_period
        $registeredDelivery = next($ar);
        next($ar); // replace_if_present_flag
        $dataCoding = next($ar);
        next($ar); // sm_default_msg_id
        $sm_length = next($ar);
        $message = $this->getString($ar, $sm_length);

        // Check for optional params, and parse them
        if (current($ar) !== false) {
            $tags = array();
            do {
                $tag = $this->parseTag($ar);
                if ($tag !== false) $tags[] = $tag;
            } while (current($ar) !== false);
        } else {
            $tags = null;
        }

        if (($esmClass & SMPP::ESM_DELIVER_SMSC_RECEIPT) != 0) {
            $sms = new SmppDeliveryReceipt($pdu->id,
                $pdu->status,
                $pdu->sequence,
                $pdu->body,
                $service_type,
                $source,
                $destination,
                $esmClass,
                $protocolId,
                $priorityFlag,
                $registeredDelivery,
                $dataCoding,
                $message,
                $tags);
            $sms->parseDeliveryReceipt();

            if (!empty($sms->id) && !empty($this->gtw_type) && in_array($this->gtw_type, ['melroselabs'])) {
                $sms->id = $this->login . '-' . $sms->id;
            }
        } else {
            $sms = new SmppSms($pdu->id,
                $pdu->status,
                $pdu->sequence,
                $pdu->body,
                $service_type,
                $source,
                $destination,
                $esmClass,
                $protocolId,
                $priorityFlag,
                $registeredDelivery,
                $dataCoding,
                $message,
                $tags);
        }

        if ($this->debug) call_user_func($this->debugHandler, "Received sms:\n" . print_r($sms, true));

        // Send response of recieving sms
        $this->sendEnquireLink($pdu->sequence);
        return $sms;
    }

    /**
     * Reads C style null padded string from the char array.
     * Reads until $maxlen or null byte.
     *
     * @param array $ar - input array
     * @param integer $maxlen - maximum length to read.
     * @param boolean $firstRead - is this the first bytes read from array?
     * @return read string.
     */
    protected function getString(&$ar, $maxlen = 255, $firstRead = false)
    {
        $s = "";
        $i = 0;
        do {
            $c = ($firstRead && $i == 0) ? current($ar) : next($ar);
            if ($c != 0) $s .= chr($c);
            $i++;
        } while ($i < $maxlen && $c != 0);
        return $s;
    }

    protected function parseTag(&$ar)
    {
        $unpackedData = unpack('nid/nlength', pack("C2C2", next($ar), next($ar), next($ar), next($ar)));
        if (!$unpackedData) throw new InvalidArgumentException('Could not read tag data',
            GatewaysErrors::GATEWAY_ERROR_NOT_TAG);
        extract($unpackedData);

        // Sometimes SMSC return an extra null byte at the end
        if ($length == 0 && $id == 0) {
            return false;
        }

        $value = $this->getOctets($ar, $length);
        $tag = new SmppTag($id, $value, $length);
        if ($this->debug) {
            call_user_func($this->debugHandler, "Parsed tag:");
            call_user_func($this->debugHandler, " id     :0x" . dechex($tag->id));
            call_user_func($this->debugHandler, " length :" . $tag->length);
            call_user_func($this->debugHandler, " value  :" . chunk_split(bin2hex($tag->value), 2, " "));
        }
        return $tag;
    }

    /**
     * Read a specific number of octets from the char array.
     * Does not stop at null byte
     *
     * @param array $ar - input array
     * @param intger $length
     */
    protected function getOctets(&$ar, $length)
    {
        $s = "";
        for ($i = 0; $i < $length; $i++) {
            $c = next($ar);
            if ($c === false) return $s;
            $s .= chr($c);
        }
        return $s;
    }

    public function sendEnquireLink($seq)
    {
        $response = new SmppPdu(SMPP::DELIVER_SM_RESP, SMPP::ESME_ROK, $seq, "\x00");
        return $this->sendPDU($response);
    }

    /**
     * Send one SMS to SMSC. Can be executed only after bindTransmitter() call.
     * $message is always in octets regardless of the data encoding.
     * For correct handling of Concatenated SMS, message must be encoded with GSM 03.38 (data_coding 0x00) or UCS-2BE (0x08).
     * Concatenated SMS'es uses 16-bit reference numbers, which gives 152 GSM 03.38 chars or 66 UCS-2BE chars per CSMS.
     *
     * @param string $message
     * @param array $tags (optional)
     * @param integer $dataCoding (optional)
     * @param integer $priority (optional)
     * @param string $scheduleDeliveryTime (optional)
     * @param string $validityPeriod (optional)
     * @return array|bool message id
     * @throws Exception
     */
    public function sendSMS(SmppAddress $from, SmppAddress $to, $message, $tags = null,
                                        $dataCoding = SMPP::DATA_CODING_DEFAULT, $priority = 0x00,
                                        $scheduleDeliveryTime = null, $validityPeriod = null): SmppPdu|array|bool
    {
        $msg_length = strlen($message);

//		if ($msg_length>160 && $dataCoding != SMPP::DATA_CODING_UCS2 && $dataCoding != SMPP::DATA_CODING_DEFAULT) return false;

        switch ($dataCoding) {
            case SMPP::DATA_CODING_UCS2:
                $singleSmsOctetLimit = 140; // in octets, 70 UCS-2 chars
                $csmsSplit =
                    132; // There are 133 octets available, but this would split the UCS the middle so use 132 instead
                break;
            case SMPP::DATA_CODING_DEFAULT:
                $singleSmsOctetLimit =
                    160; // we send data in octets, but GSM 03.38 will be packed in septets (7-bit) by SMSC.
                $csmsSplit =
                    152; // send 152 chars in each SMS since, we will use 16-bit CSMS ids (SMSC will format data)
                break;
            default:
                $singleSmsOctetLimit = 254; // From SMPP standard
                break;
        }

        // Figure out if we need to do CSMS, since it will affect our PDU
        if ($msg_length > $singleSmsOctetLimit) {
            $doCsms = true;
            if (self::$csms_method != SmppClient::CSMS_PAYLOAD) {
                $parts = $this->splitMessageString($message, $csmsSplit, $dataCoding);
                $short_message = reset($parts);
                $csmsReference = $this->getCsmsReference();
            }
        } else {
            $short_message = $message;
            $doCsms = false;
        }

        // Deal with CSMS
        if ($doCsms) {
            try {
                if (self::$csms_method == SmppClient::CSMS_PAYLOAD) {
                    $payload = array(new SmppTag(SmppTag::MESSAGE_PAYLOAD, $message, $msg_length));
                    return $this->submit_sm($from,
                        $to,
                        null,
                        (empty($tags) ? $payload : array_merge($tags, $payload)),
                        $dataCoding,
                        $priority,
                        $scheduleDeliveryTime,
                        $validityPeriod);
                } else if (self::$csms_method == SmppClient::CSMS_8BIT_UDH) {
                    $seqnum = 1;
                    foreach ($parts as $part) {
                        $udh = pack('cccccc', 5, 0, 3, substr($csmsReference, 1, 1), count($parts), $seqnum);
                        $res = $this->submit_sm($from,
                            $to,
                            $udh . $part,
                            $tags,
                            $dataCoding,
                            $priority,
                            $scheduleDeliveryTime,
                            $validityPeriod,
                            (SmppClient::$sms_esm_class | 0x40));
                        $seqnum++;
                    }
                    return $res;
                } else {
                    $sar_msg_ref_num = new SmppTag(SmppTag::SAR_MSG_REF_NUM, $csmsReference, 2, 'n');
                    $sar_total_segments = new SmppTag(SmppTag::SAR_TOTAL_SEGMENTS, count($parts), 1, 'c');
                    $seqnum = 1;
                    foreach ($parts as $part) {
                        $sartags = array($sar_msg_ref_num, $sar_total_segments,
                            new SmppTag(SmppTag::SAR_SEGMENT_SEQNUM, $seqnum, 1, 'c'));
                        $res = $this->submit_sm($from,
                            $to,
                            $part,
                            (empty($tags) ? $sartags : array_merge($tags, $sartags)),
                            $dataCoding,
                            $priority,
                            $scheduleDeliveryTime,
                            $validityPeriod);
                        $seqnum++;
                    }
                    return $res;
                }
            } catch (Exception $e) {
                $e_msg = $e->getMessage();

                # no retries/reconnect for CLX|Commpeak - as they have long bind... we have queue...
                # do it for all - no reason to reconnect (we always have fresh connection)
//                if (!empty($this->gtw_type) && in_array($this->gtw_type, ['clx', 'commpeak', 'smswarriors', 'hansa', 't2w', 'clickatell', 'quickcom'])) {
                $e_msg .= "\n[" . $e->getLine() . '] [' . $e->getFile() . ']';
                throw new Exception("[] submit_sm failed: {$e_msg}",
                    $e->getCode(),
                    $e);
//                }
                /*                echo "\n".'submit_sm doCsms Exception: ['.$e_msg.']'."\n";

                                if (static::$retries >= self::MAX_RETRIES) {
                                    static::$retries = 1;
                                    throw new \Exception("submit_sm failed fot at least 3 time, {$e_msg}", 500, $e);
                                }
                                static::reconnect();
                                static::$retries++;

                                return $this->sendSMS($from, $to, $message, $tags, $dataCoding, $priority, $scheduleDeliveryTime, $validityPeriod);
                */
            }
        }

        try {
            return $this->submit_sm($from, $to, $short_message, $tags, $dataCoding);
        } catch (Exception $e) {
            $e_msg = $e->getMessage();

            # no retries/reconnect for CLX|Commpeak - as they have long bind... we have queue...
            # do it for all - no reason to reconnect (we always have fresh connection)
//            if (!empty($this->gtw_type) && in_array($this->gtw_type, ['clx', 'commpeak', 'smswarriors', 'hansa', 't2w', 'clickatell', 'quickcom'])) {
            $e_msg .= "\n[" . $e->getLine() . '] [' . $e->getFile() . ']';
            throw new Exception("[" . strtoupper($this->gtw_type) . "] submit_sm failed: {$e_msg}", $e->getCode(), $e);
//            }
            /*
                        echo "\n".'submit_sm Exception: ['.$e_msg.']'."\n";

                        if (static::$retries >= self::MAX_RETRIES) {
                            static::$retries = 1;
                            throw new \Exception("submit_sm failed fot at least 3 time, {$e_msg}", 500, $e);
                        }
                        static::reconnect();
                        static::$retries++;

                        return $this->sendSMS($from, $to, $message, $tags, $dataCoding, $priority, $scheduleDeliveryTime, $validityPeriod);
            */
        }
    }

    /**
     * Split a message into multiple parts, taking the encoding into account.
     * A character represented by an GSM 03.38 escape-sequence shall not be split in the middle.
     * Uses str_split if at all possible, and will examine all split points for escape chars if it's required.
     *
     * @param string $message
     * @param integer $split
     * @param integer $dataCoding (optional)
     */
    protected function splitMessageString($message, $split, $dataCoding = SMPP::DATA_CODING_DEFAULT)
    {
        switch ($dataCoding) {
            case SMPP::DATA_CODING_DEFAULT:
                $msg_length = strlen($message);
                // Do we need to do php based split?
                $numParts = floor($msg_length / $split);
                if ($msg_length % $split == 0) $numParts--;
                $slowSplit = false;

                for ($i = 1; $i <= $numParts; $i++) {
                    if ($message[$i * $split - 1] == "\x1B") {
                        $slowSplit = true;
                        break;
                    }
                }
                if (!$slowSplit) return str_split($message, $split);

                // Split the message char-by-char
                $parts = array();
                $part = null;
                $n = 0;
                for ($i = 0; $i < $msg_length; $i++) {
                    $c = $message[$i];
                    // reset on $split or if last char is a GSM 03.38 escape char
                    if ($n == $split || ($n == ($split - 1) && $c == "\x1B")) {
                        $parts[] = $part;
                        $n = 0;
                        $part = null;
                    }
                    $part .= $c;
                }
                $parts[] = $part;
                return $parts;
            case SMPP::DATA_CODING_UCS2: // UCS2-BE can just use str_split since we send 132 octets per message, which gives a fine split using UCS2
            default:
                return str_split($message, $split);
        }
    }

    /**
     * Get a CSMS reference number for sar_msg_ref_num.
     * Initializes with a random value, and then returns the number in sequence with each call.
     */
    protected function getCsmsReference()
    {
        $limit = (SmppClient::$csms_method == SmppClient::CSMS_8BIT_UDH) ? 255 : 65535;
        if (!isset($this->sar_msg_ref_num)) $this->sar_msg_ref_num = mt_rand(0, $limit);
        $this->sar_msg_ref_num++;
        if ($this->sar_msg_ref_num > $limit) $this->sar_msg_ref_num = 0;
        return $this->sar_msg_ref_num;
    }

    /**
     * Perform the actual submit_sm call to send SMS.
     * Implemented as a protected method to allow automatic sms concatenation.
     * Tags must be an array of already packed and encoded TLV-params.
     *
     * @param string $short_message
     * @param array $tags
     * @param integer $dataCoding
     * @param integer $priority
     * @param string $scheduleDeliveryTime
     * @param string $validityPeriod
     * @param string $esmClass
     * @throws Exception
     */
    protected function submit_sm(SmppAddress $source, SmppAddress $destination, $short_message = null, $tags = null,
                                             $dataCoding = SMPP::DATA_CODING_DEFAULT, $priority = 0x00,
                                             $scheduleDeliveryTime = null, $validityPeriod = null,
                                             $esmClass = null): array|SmppPdu
    {
        if (is_null($esmClass)) $esmClass = self::$sms_esm_class;

        // Construct PDU with mandatory fields
        $pdu = pack('a1cca' . (strlen($source->value) + 1) . 'cca' . (strlen($destination->value) + 1) . 'ccc' .
            ($scheduleDeliveryTime ? 'a16x' : 'a1') . ($validityPeriod ? 'a16x' : 'a1') . 'ccccca' .
            (strlen($short_message) + (self::$sms_null_terminate_octetstrings ? 1 : 0)),
            self::$sms_service_type,
            $source->ton,
            $source->npi,
            $source->value,
            $destination->ton,
            $destination->npi,
            $destination->value,
            $esmClass,
            self::$sms_protocol_id,
            $priority,
            $scheduleDeliveryTime,
            $validityPeriod,
            self::$sms_registered_delivery_flag,
            self::$sms_replace_if_present_flag,
            $dataCoding,
            self::$sms_sm_default_msg_id,
            strlen($short_message),//sm_length
            $short_message//short_message
        );

        // Add any tags
        if (!empty($tags)) {
            foreach ($tags as $tag) {
                $pdu .= $tag->getBinary();
            }
        }

        try {
            $results = $this->sendCommand(SMPP::SUBMIT_SM, $pdu);
        } catch (Exception $e) {
            $msg = "submit_sm failed\n {$e->getMessage()}";

            throw new Exception($msg, $e->getCode());
        }
        $response = $results['response'];
        $body = unpack("a*msgid", $response->body);
        unset($results['response']);

        # nexmo... returns TLVs for mmc/price/balance
        $nn = strpos(trim($response->body), chr(0));

        if ($nn !== false) {

            $body = substr($response->body, 0, $nn);
            $body = unpack("a*msgid", $body);

            $extra = substr($response->body, $nn);
            $extra = trim($extra);

            if (!empty($extra)) {
                try {
                    $dl = strlen($extra);
                    $pos = 0;
                    $tlvs = [];
                    while ($pos < $dl) {

                        $t = implode(null, unpack('n', substr($extra, $pos, 2)));
                        $pos += 2;
                        if ($t == null || $t == false) {
                            break;
                        }
                        $l = implode(null, unpack('n', substr($extra, $pos, 2)));
                        $pos += 2;
                        $v = implode(null, unpack('a*', substr($extra, $pos, $l)));
                        $pos += $l;

                        $tlvs[] = new SmppTag($t, $v, $l);
                    }
                } catch (Exception) {
                }

                if (empty($tlvs)) {
                    $tlvs = [$extra];
                }

                $results['tlvs'] = $tlvs;
            }
        }

        $results['msgid'] = trim($body['msgid']);

        if (!empty($this->gtw_type) && in_array($this->gtw_type, ['melroselabs'])) {
            $results['msgid'] = $this->login . '-' . $results['msgid'];
        }
        return $results;
    }

    /**
     * This will send HLR request over smpp
     * before smpp::getSmppClient system_type should be set to 'HLR' (SmppClient::$system_type = 'HLR')
     *
     * @param $number | should contain an international formatted phone number (e.x 972542962889)
     * @return string
     * @throws InvalidArgumentException
     */
    public function sendHlr($number)
    {
        $to = new SmppAddress($number, SMPP::TON_INTERNATIONAL, SMPP::NPI_E164);

        try {
            return $this->submit_sm($to, $to);
        } catch (Exception $e) {
            if (static::$retries >= self::MAX_RETRIES) {
                static::$retries = 1;
                throw new Exception("HLR | submit_sm failed for 3 times, {$e->getMessage()}", $e->getCode(), $e);
            }
            static::$retries++;
            static::reconnect();
            $this->sendHlr($number);
        }
    }

    public function enquireLink($sleep = 25)
    {
        $sleep = (int)$sleep;
        if (empty($sleep) || $sleep < 10) {
            $sleep = 10;
        }

        if (empty($this->last_enquire_link) || ($this->last_enquire_link < (time() - $sleep))) {

            $this->last_enquire_link = time();

            if ($this->debug) {
                call_user_func($this->debugHandler, 'Sending ENQUIRE_LINK...');
            }

            return $this->sendCommand(SMPP::ENQUIRE_LINK, null);
        }
    }

    /**
     * Respond to any enquire link we might have waiting.
     * If will check the queue first and respond to any enquire links we have there.
     * Then it will move on to the transport, and if the first PDU is enquire link respond,
     * otherwise add it to the queue and return.
     *
     */
    public function respondEnquireLink()
    {
        // Check the queue first
        $ql = count($this->pdu_queue);
        for ($i = 0; $i < $ql; $i++) {
            $pdu = $this->pdu_queue[$i];
            if ($pdu->id == SMPP::ENQUIRE_LINK) {
                //remove response
                array_splice($this->pdu_queue, $i, 1);
                $this->sendPDU(new SmppPdu(SMPP::ENQUIRE_LINK_RESP, SMPP::ESME_ROK, $pdu->sequence, ""));
            }
        }

        // Check the transport for data
        if ($this->transport->hasData()) {
            $pdu = $this->readPDU();
            if ($pdu->id == SMPP::ENQUIRE_LINK) {
                $this->sendPDU(new SmppPdu(SMPP::ENQUIRE_LINK_RESP, SMPP::ESME_ROK, $pdu->sequence, ""));
            } else if ($pdu) {
                array_push($this->pdu_queue, $pdu);
            }
        }
    }
}
