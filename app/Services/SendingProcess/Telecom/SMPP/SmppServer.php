<?php

namespace App\Services\SendingProcess\Telecom\SMPP;

use App\Services\SendingProcess\Telecom\SMPP\Server\PDU;
use DateTime;

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * SmppServer - main file
 *
 * SmppServer is an implementation of the SMPP v3.3 and v3.4 protocols.
 * Portions of the documentation are reproduced with permission from the
 * SMPP v3.4 Specification, Issue 1.2, (c) 1999 SMPP Developers Forum.
 *
 * This document is essential reading to make use of SmppServer, and may be
 * freely downloaded from {@link http://smsforum.net/doc/download.php?id=smppv34}
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Networking
 * @package    SmppServer
 * @author     Ian Eure <ieure@php.net>
 * @copyright  (c) Copyright 2005 WebSprockets, LLC.
 * @copyright  Portions of the documentation (c) Copyright 1999 SMPP Developers
 *             Forum.
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @version    CVS:     $Revision: 298083 $
 * @link       http://pear.php.net/package/Net_SMPP
 */

// Place includes, constant defines and $_GLOBAL settings here.

define('MAX_SEQ', 2147483646);
define('SERVER_SMPP_VERSION_33', 0x33);
define('SERVER_SMPP_VERSION_34', 0x34);

/**
 * Main SmppServer class
 *
 * This class contains a few methods for handling top-level SMPP actions.
 *
 * @category   Networking
 * @package    SmppServer
 * @author     Ian Eure <ieure@php.net>
 * @copyright  (c) Copyright 2005 WebSprockets, LLC.
 * @copyright  Portions of the documentation (c) Copyright 1999 SMPP Developers
 *             Forum.
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @version    CVS:     $Revision: 298083 $
 * @link       http://pear.php.net/package/Net_SMPP
 * @static
 */
class SmppServer
{
    const STATE_CLOSED = 0;
    const STATE_OPEN = 1;
    const STATE_BOUND_TX = 2;
    const STATE_BOUND_RX = 3;
    const STATE_BOUND_TRX = 4;
    const DLR_ENROUTE = 1;
    const DLR_DELIVRD = 2;
    const DLR_EXPIRED = 3;
    const DLR_DELETED = 4;
    const DLR_UNDELIV = 5;
    const DLR_ACCEPTD = 6;
    const DLR_UNKNOWN = 7;
    const DLR_REJECTD = 8;
    const ERROR_INVALID_SOURCE = 770;
    const ERROR_INVALID_DESTINATION = 771;

    # DLR status codes
    const ERROR_NO_DESTINATION = 772;
    const ERROR_INTERNAL = 773;
    const ERROR_INTERNAL_DB = 774;
    const ERROR_ENROUTE = 775;
    const ERROR_API = 776; # Exception
    const ERROR_SEND = 777; # Exception on DB query
    const ERROR_NO_DLR = 778;
    const ERROR_MISSED_PARTS = 779;
    public static $states = array(
        'bind_transmitter' => array(self::STATE_OPEN),
        'bind_transmitter_resp' => array(self::STATE_OPEN),
        'bind_receiver' => array(self::STATE_OPEN),
        'bind_receiver_resp' => array(self::STATE_OPEN),
        'bind_transceiver' => array(self::STATE_OPEN),
        'bind_transceiver_resp' => array(self::STATE_OPEN),
        'outbind' => array(self::STATE_OPEN),
        'unbind' => array(self::STATE_BOUND_TX, self::STATE_BOUND_RX, self::STATE_BOUND_TRX),
        'unbind_resp' => array(self::STATE_BOUND_TX, self::STATE_BOUND_RX, self::STATE_BOUND_TRX),
        'submit_sm' => array(self::STATE_BOUND_TX, self::STATE_BOUND_TRX),
        'submit_sm_resp' => array(self::STATE_BOUND_TX, self::STATE_BOUND_TRX),
        'submit_sm_multi' => array(self::STATE_BOUND_TX, self::STATE_BOUND_TRX),
        'submit_sm_multi_resp' => array(self::STATE_BOUND_TX, self::STATE_BOUND_TRX),
        'data_sm' => array(self::STATE_BOUND_TX, self::STATE_BOUND_RX, self::STATE_BOUND_TRX),
        'data_sm_resp' => array(self::STATE_BOUND_TX, self::STATE_BOUND_RX, self::STATE_BOUND_TRX),
        'deliver_sm' => array(self::STATE_BOUND_RX, self::STATE_BOUND_TRX),
        'deliver_sm_resp' => array(self::STATE_BOUND_RX, self::STATE_BOUND_TRX),
        'query_sm' => array(self::STATE_BOUND_RX, self::STATE_BOUND_TRX),
        'query_sm_resp' => array(self::STATE_BOUND_RX, self::STATE_BOUND_TRX),
        'cancel_sm' => array(self::STATE_BOUND_RX, self::STATE_BOUND_TRX),
        'cancel_sm_resp' => array(self::STATE_BOUND_RX, self::STATE_BOUND_TRX),
        'replace_sm' => array(self::STATE_BOUND_TX),
        'replace_sm_resp' => array(self::STATE_BOUND_TX),
        'enquire_link' => array(self::STATE_BOUND_TX, self::STATE_BOUND_RX, self::STATE_BOUND_TRX),
        'enquire_link_resp' => array(self::STATE_BOUND_TX, self::STATE_BOUND_RX, self::STATE_BOUND_TRX),
        'alert_notification' => array(self::STATE_BOUND_RX, self::STATE_BOUND_TRX),
        'generic_nack' => array(self::STATE_BOUND_TX, self::STATE_BOUND_RX, self::STATE_BOUND_TRX),
    );
    public static $setters = array(
        'bind_transmitter_resp' => self::STATE_BOUND_TX,
        'bind_receiver_resp' => self::STATE_BOUND_RX,
        'bind_transceiver_resp' => self::STATE_BOUND_TRX,
        'unbind_resp' => self::STATE_OPEN,
    );
    public static $message_states = array(
        self::DLR_ENROUTE => 'ENROUTE',
        self::DLR_DELIVRD => 'DELIVRD',
        self::DLR_EXPIRED => 'EXPIRED',
        self::DLR_DELETED => 'DELETED',
        self::DLR_UNDELIV => 'UNDELIV',
        self::DLR_ACCEPTD => 'ACCEPTD',
        self::DLR_UNKNOWN => 'UNKNOWN',
        self::DLR_REJECTD => 'REJECTD',
    );

    # DLR ERROR codes
    public static $message_errors = array(
        self::ERROR_INVALID_SOURCE => 'Invalid source',
        self::ERROR_INVALID_DESTINATION => 'Invalid destination',
        self::ERROR_NO_DESTINATION => 'No route',
        self::ERROR_INTERNAL => 'Internal error',
        self::ERROR_INTERNAL_DB => 'Internal DB error',
        self::ERROR_ENROUTE => 'Can not enroute',
        self::ERROR_API => 'API error',
        self::ERROR_SEND => 'Can not send to provider',
        self::ERROR_NO_DLR => 'No DLR received (3h timeout)',
        self::ERROR_MISSED_PARTS => 'Missed parts',
    );

    public static function parsePDU($data)
    {
        $command = PDU::extractCommand($data);

        if ($command === false) {
            return false;
        }

        $pdu = self::PDU($command, array('sequence' => 'dummy'));
        $pdu->parse($data);

        return $pdu;
    }

    public static function PDU($command, $args = array(), $status = SERVER_SMPP_ESME_ROK)
    {
        return PDU::factory($command, $args, $status);
    }

    public static function messageIdEncode($id)
    {
        return strtoupper(dechex($id));
    }

    public static function messageIdDecode($hex)
    {
        return hexdec($hex);
    }

    public static function makeInsert($user_id, $raw, $pdu)
    {
        $columns = [
            'user_id' => (int)$user_id,
            'registered_delivery' => null,
            'esm_class' => null,
            'data_coding' => null,
            'sm_length' => null,
            'user_message_reference' => null,
            'sar_msg_ref_num' => null,
            'sar_total_segments' => null,
            'sar_segment_seqnum' => null,
            'payload_type' => null,
            'source_addr_ton' => null,
            'source_addr_npi' => null,
            'dest_addr_ton' => null,
            'dest_addr_npi' => null,
            'service_type' => null,
            'source_addr' => null,
            'destination_addr' => null,
            'schedule_delivery_time' => null,
            'validity_period' => null,
            'short_message' => null,
            'message_payload' => null,
            'pdu' => $raw,
        ];

        $insert = [];

        foreach ($columns as $col => $v) {
            if (property_exists($pdu, $col) && !is_null($pdu->$col) && strlen($pdu->$col)) {
                if ($col == 'short_message' || $col == 'message_payload') {
                    $insert[$col] = str_replace('"', '\"', $pdu->$col);
                } else {
                    $insert[$col] = $pdu->$col;
                }
            } elseif (!is_null($v)) {
                $insert[$col] = $v;
            }
        }

        return $insert;
    }

    public static function generateDlrMessage($id, $submit_date, $done_date, $status, $msg, $error = 0)
    {
        if (!is_int($submit_date)) {
            $submit_date = strtotime($submit_date);
        }
        if (!is_int($done_date)) {
            $done_date = strtotime($done_date);
        }
        if (empty($submit_date)) {
            $submit_date = time();
        }
        if (empty($done_date)) {
            $done_date = time();
        }

        $stat = strtoupper(self::$message_states[$status]);

        $error = (int)$error;
        if ($status == self::DLR_DELIVRD) {
            $error = 0;
        }
        $msg = @substr(utf8_decode($msg), 0, 20);
        if (empty($msg)) {
            $msg = '-';
        }

        return 'id:' . $id . ' sub:001 dlvrd:001 submit date:' . date('ymdHm', $submit_date) . ' done date:' .
            date('ymdHm', $done_date) . ' stat:' . $stat . ' err:' . str_pad($error, 3, '0') . ' text:' . $msg;
    }

    public static function parseDate($str)
    {
        $time = false;

        if (str_ends_with($str, 'R')) {
            # relative
            $d = str_split($str, 2);
            if (!empty($d)) {
                foreach ($d as $k => $v) {
                    $d[$k] = (int)$v;
                }
                $format = ['+'];
                if (!empty($d[0])) {
                    $format[] = $d[0] . ' years';
                }
                if (!empty($d[1])) {
                    $format[] = $d[1] . ' months';
                }
                if (!empty($d[2])) {
                    $format[] = $d[2] . ' days';
                }
                if (!empty($d[3])) {
                    $format[] = $d[3] . ' hours';
                }
                if (!empty($d[4])) {
                    $format[] = $d[4] . ' minutes';
                }
                if (!empty($d[5])) {
                    $format[] = $d[5] . ' seconds';
                }
                $time = strtotime(implode(' ', $format));
            }
        } else {
            # absolute
            $d = DateTime::createFromFormat('ymdHis', substr($str, 0, 12));
            $d->modify(substr($str, -1) . intval(substr($str, 13, 2)) . ' hours');
            $time = $d->getTimestamp();
        }

        return $time;
    }
}

?>
