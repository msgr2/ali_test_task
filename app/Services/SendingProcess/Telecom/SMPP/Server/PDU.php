<?php

namespace App\Services\SendingProcess\Telecom\SMPP\Server;

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

use App\Services\SendingProcess\Telecom\SMPP\Server\Command\AlertNotification;
use App\Services\SendingProcess\Telecom\SMPP\Server\Command\BindReceiver;
use App\Services\SendingProcess\Telecom\SMPP\Server\Command\BindReceiverResp;
use App\Services\SendingProcess\Telecom\SMPP\Server\Command\BindTransceiver;
use App\Services\SendingProcess\Telecom\SMPP\Server\Command\BindTransceiverResp;
use App\Services\SendingProcess\Telecom\SMPP\Server\Command\BindTransmitter;
use App\Services\SendingProcess\Telecom\SMPP\Server\Command\BindTransmitterResp;
use App\Services\SendingProcess\Telecom\SMPP\Server\Command\DataSm;
use App\Services\SendingProcess\Telecom\SMPP\Server\Command\DataSmResp;
use App\Services\SendingProcess\Telecom\SMPP\Server\Command\DeliverSm;
use App\Services\SendingProcess\Telecom\SMPP\Server\Command\DeliverSmResp;
use App\Services\SendingProcess\Telecom\SMPP\Server\Command\EnquireLink;
use App\Services\SendingProcess\Telecom\SMPP\Server\Command\EnquireLinkResp;
use App\Services\SendingProcess\Telecom\SMPP\Server\Command\GenericNack;
use App\Services\SendingProcess\Telecom\SMPP\Server\Command\SubmitSm;
use App\Services\SendingProcess\Telecom\SMPP\Server\Command\SubmitSmResp;
use App\Services\SendingProcess\Telecom\SMPP\Server\Command\Unbind;
use App\Services\SendingProcess\Telecom\SMPP\Server\Command\UnbindResp;
use Exception;

/**
 * SMPP PDU support
 *
 * This file contains the ServerPDU class and various constants which are
 * needed for them to work.
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
 * @package    Server
 * @author     Ian Eure <ieure@php.net>
 * @copyright  (c) Copyright 2005 WebSprockets, LLC.
 * @copyright  Portions of the documentation (c) Copyright 1999 SMPP Developers
 *             Forum.
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @version    CVS:     $Revision: 298083 $
 * @since      Release: 0.0.1dev1
 * @link       http://pear.php.net/package/Net_SMPP
 */

// Place includes, constant defines and $_GLOBAL settings here.

/**
 * SMPP error codes
 */
define('SERVER_SMPP_ESME_ROK', 0x00000000);
define('SERVER_SMPP_ESME_RINVMSGLEN', 0x00000001);
define('SERVER_SMPP_ESME_RINVCMDLEN', 0x00000002);
define('SERVER_SMPP_ESME_RINVCMDID', 0x00000003);
define('SERVER_SMPP_ESME_RINVBNDSTS', 0x00000004);
define('SERVER_SMPP_ESME_RALYBND', 0x00000005);
define('SERVER_SMPP_ESME_RINVPRTFLG', 0x00000006);
define('SERVER_SMPP_ESME_RINVREGDLVFLG', 0x00000007);
define('SERVER_SMPP_ESME_RSYSERR', 0x00000008);
define('SERVER_SMPP_ESME_RINVSRCADR', 0x0000000A);
define('SERVER_SMPP_ESME_RINVDSTADR', 0x0000000B);
define('SERVER_SMPP_ESME_RINVMSGID', 0x0000000C);
define('SERVER_SMPP_ESME_RBINDFAIL', 0x0000000D);
define('SERVER_SMPP_ESME_RINVPASWD', 0x0000000E);
define('SERVER_SMPP_ESME_RINVSYSID', 0x0000000F);
define('SERVER_SMPP_ESME_RCANCELFAIL', 0x00000011);
define('SERVER_SMPP_ESME_RREPLACEFAIL', 0x00000013);
define('SERVER_SMPP_ESME_RMSGQFUL', 0x00000014);
define('SERVER_SMPP_ESME_RINVSERTYP', 0x00000015);
define('SERVER_SMPP_ESME_RINVNUMDESTS', 0x00000033);
define('SERVER_SMPP_ESME_RINVDLNAME', 0x00000034);
define('SERVER_SMPP_ESME_RINVDESTFLAG', 0x00000040);
define('SERVER_SMPP_ESME_RINVSUBREP', 0x00000042);
define('SERVER_SMPP_ESME_RINVESMCLASS', 0x00000043);
define('SERVER_SMPP_ESME_RCNTSUBDL', 0x00000044);
define('SERVER_SMPP_ESME_RSUBMITFAIL', 0x00000045);
define('SERVER_SMPP_ESME_RINVSRCTON', 0x00000048);
define('SERVER_SMPP_ESME_RINVSRCNPI', 0x00000049);
define('SERVER_SMPP_ESME_RINVDSTTON', 0x00000050);
define('SERVER_SMPP_ESME_RINVDSTNPI', 0x00000051);
define('SERVER_SMPP_ESME_RINVSYSTYP', 0x00000053);
define('SERVER_SMPP_ESME_RINVREPFLAG', 0x00000054);
define('SERVER_SMPP_ESME_RINVNUMMSGS', 0x00000055);
define('SERVER_SMPP_ESME_RTHROTTLED', 0x00000058);
define('SERVER_SMPP_ESME_RINVSCHED', 0x00000061);
define('SERVER_SMPP_ESME_RINVEXPIRY', 0x00000062);
define('SERVER_SMPP_ESME_RINVDFTMSGID', 0x00000063);
define('SERVER_SMPP_ESME_RX_T_APPN', 0x00000064);
define('SERVER_SMPP_ESME_RX_P_APPN', 0x00000065);
define('SERVER_SMPP_ESME_RX_R_APPN', 0x00000066);
define('SERVER_SMPP_ESME_RQUERYFAIL', 0x00000067);
define('SERVER_SMPP_ESME_RINVOPTPARSTREAM', 0x000000C0);
define('SERVER_SMPP_ESME_ROPTPARNOTALLWD', 0x000000C1);
define('SERVER_SMPP_ESME_RINVPARLEN', 0x000000C2);
define('SERVER_SMPP_ESME_RMISSINGOPTPARAM', 0x000000C3);
define('SERVER_SMPP_ESME_RINVOPTPARAMVAL', 0x000000C4);
define('SERVER_SMPP_ESME_RDELIVERYFAILURE', 0x000000FE);
define('SERVER_SMPP_ESME_RUNKNOWNERR', 0x000000FF);


/**
 * SMPP PDU (Protocol Data Unit) class
 *
 * This is the lowest-level class for handling PDUs, and it is responsible for
 * generating the PDU header, among other things.
 *
 * The design of this class is:
 *
 * ServerCommandfoobar
 *  -> ServerCommand
 *    -> ServerPDU
 *
 * The ServerCommand_foobar class defines the paramaters which may be set
 * for any given command. ServerCommand has methods which operate on the
 * command definitions in ServerCommand_foobar, en/decode the binary
 * protocol data, and so forth.
 *
 * Simple example; send_sm command:
 * require_once 'Server.php';
 * $ssm =& Server::PDU('submit_sm');
 * $ssm->short_message = 'Testing';
 * // Generate the binary protocol data
 * $pdu = $ssm->generate();
 *
 * @category   Networking
 * @package    Server
 * @author     Ian Eure <ieure@php.net>
 * @copyright  (c) Copyright 2005 WebSprockets, LLC.
 * @copyright  Portions of the documentation (c) Copyright 1999 SMPP Developers
 *             Forum.
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @version    CVS:     $Revision: 298083 $
 * @since      Release: 0.0.1dev1
 * @link       http://pear.php.net/package/Net_SMPP
 */
class PDU
{
    // Header
    protected static $_descs = array(
        SERVER_SMPP_ESME_ROK => 'No Error',
        SERVER_SMPP_ESME_RINVMSGLEN => 'Message Length is invalid',
        SERVER_SMPP_ESME_RINVCMDLEN => 'Command Length is invalid',
        SERVER_SMPP_ESME_RINVCMDID => 'Invalid Command ID',
        SERVER_SMPP_ESME_RINVBNDSTS => 'Incorrect BIND Status for given command',
        SERVER_SMPP_ESME_RALYBND => 'ESME Already in Bound State',
        SERVER_SMPP_ESME_RINVPRTFLG => 'Invalid Priority Flag',
        SERVER_SMPP_ESME_RSYSERR => 'System Error',
        SERVER_SMPP_ESME_RINVSRCADR => 'Invalid Source Address',
        SERVER_SMPP_ESME_RINVDSTADR => 'Invalid Dest Addr',
        SERVER_SMPP_ESME_RINVMSGID => 'Message ID is invalid',
        SERVER_SMPP_ESME_RBINDFAIL => 'Bind Failed',
        SERVER_SMPP_ESME_RINVPASWD => 'Invalid Password',
        SERVER_SMPP_ESME_RINVSYSID => 'Invalid System ID',
        SERVER_SMPP_ESME_RCANCELFAIL => 'Cancel SM Failed',
        SERVER_SMPP_ESME_RREPLACEFAIL => 'Replace SM Failed',
        SERVER_SMPP_ESME_RMSGQFUL => 'Message Queue Full',
        SERVER_SMPP_ESME_RINVSERTYP => 'Invalid Service Type',
        SERVER_SMPP_ESME_RINVNUMDESTS => 'Invalid number of destinations',
        SERVER_SMPP_ESME_RINVDLNAME => 'Invalid Distribution List name',
        SERVER_SMPP_ESME_RINVDESTFLAG => 'Destination flag is invalid (submit_multi)',
        SERVER_SMPP_ESME_RINVSUBREP => 'Invalid ‘submit with replace’ request (i.e. submit_sm with replace_if_present_flag set)',
        SERVER_SMPP_ESME_RINVESMCLASS => 'Invalid esm_class field data',
        SERVER_SMPP_ESME_RCNTSUBDL => 'Cannot Submit to Distribution List',
        SERVER_SMPP_ESME_RSUBMITFAIL => 'submit_sm or submit_multi failed',
        SERVER_SMPP_ESME_RINVSRCTON => 'Invalid Source address TON',
        SERVER_SMPP_ESME_RINVSRCNPI => 'Invalid Source address NPI',
        SERVER_SMPP_ESME_RINVDSTTON => 'Invalid Destination address TON',
        SERVER_SMPP_ESME_RINVDSTNPI => 'Invalid Destination address NPI',
        SERVER_SMPP_ESME_RINVSYSTYP => 'Invalid system_type field',
        SERVER_SMPP_ESME_RINVREPFLAG => 'Invalid replace_if_present flag',
        SERVER_SMPP_ESME_RINVNUMMSGS => 'Invalid number of messages',
        SERVER_SMPP_ESME_RTHROTTLED => 'Throttling error (ESME has exceeded allowed message limits)',
        SERVER_SMPP_ESME_RINVSCHED => 'Invalid Scheduled Delivery Time',
        SERVER_SMPP_ESME_RINVEXPIRY => 'Invalid message validity  period (Expiry time)',
        SERVER_SMPP_ESME_RINVDFTMSGID => 'Predefined Message Invalid or Not Found',
        SERVER_SMPP_ESME_RX_T_APPN => 'ESME Receiver Temporary App Error Code',
        SERVER_SMPP_ESME_RX_P_APPN => 'ESME Receiver Permanent App Error Code',
        SERVER_SMPP_ESME_RX_R_APPN => 'ESME Receiver Reject Message Error Code',
        SERVER_SMPP_ESME_RQUERYFAIL => 'query_sm request failed',
        SERVER_SMPP_ESME_RINVOPTPARSTREAM => 'Error in the optional part of the PDU Body.',
        SERVER_SMPP_ESME_ROPTPARNOTALLWD => 'Optional Parameter not allowed',
        SERVER_SMPP_ESME_RINVPARLEN => 'Invalid Parameter Length.',
        SERVER_SMPP_ESME_RMISSINGOPTPARAM => 'Expected Optional Parameter missing',
        SERVER_SMPP_ESME_RINVOPTPARAMVAL => 'Invalid Optional Parameter Value',
        SERVER_SMPP_ESME_RDELIVERYFAILURE => 'Delivery Failure (used for data_sm_resp)',
        SERVER_SMPP_ESME_RUNKNOWNERR => 'Unknown Error'
    );
    /**
     * PDU command
     *
     * @var  int
     */
    public $command = null;

    /**
     * Status of the command
     *
     * This is only relevant for response PDUs
     *
     * @var  int
     */
    public $status = null;

    /**
     * PDU sequence
     *
     * @see  Server::nextSeq()
     * @var  int
     */
    public $sequence = 0;
    /**
     * Octal length of the total PDU
     *
     * @var     int
     * @access  protected
     */
    protected $_length = 0;

    public static function factory($command, $args = array(), $status = SERVER_SMPP_ESME_ROK)
    {
        $command = ucwords($command, '_');
        $class = str_replace('_', '', $command);
        return match ($class) {
            'AlertNotification' => new AlertNotification($command, $args, $status),
            'BindReceiver' => new BindReceiver($command, $args, $status),
            'BindReceiverResp' => new BindReceiverResp($command, $args, $status),
            'BindTransceiver' => new BindTransceiver($command, $args, $status),
            'BindTransceiverResp' => new BindTransceiverResp($command, $args, $status),
            'BindTransmitter' => new BindTransmitter($command, $args, $status),
            'BindTransmitterResp' => new BindTransmitterResp($command, $args, $status),
            'DataSm' => new DataSm($command, $args, $status),
            'DataSmResp' => new DataSmResp($command, $args, $status),
            'DeliverSm' => new DeliverSm($command, $args, $status),
            'DeliverSmResp' => new DeliverSmResp($command, $args, $status),
            'EnquireLink' => new EnquireLink($command, $args, $status),
            'EnquireLinkResp' => new EnquireLinkResp($command, $args, $status),
            'GenericNack' => new GenericNack($command, $args, $status),
            'SubmitSm' => new SubmitSm($command, $args, $status),
            'SubmitSmResp' => new SubmitSmResp($command, $args, $status),
            'Unbind' => new Unbind($command, $args, $status),
            'UnbindResp' => new UnbindResp($command, $args, $status),
            default => false,
        };
    }

    /**
     * Is this a request PDU?
     *
     * @return  boolean  true if it is, false otherwise
     */
    public function isRequest()
    {
        return !$this->isResponse();
    }

    /**
     * Is this a response PDU?
     *
     * @return  boolean  true if it is, false otherwise
     */
    public function isResponse()
    {
        if (Command::commandCode($this->command) & 0x80000000) {
            return true;
        }
        return false;
    }

    /**
     * Is this an error response?
     *
     * @return  boolean  true if it is, false otherwise
     */
    public function isError()
    {
        if ($this->status != SERVER_SMPP_ESME_ROK) {
            return true;
        }
    }

    /**
     * Get status description
     *
     * @param int $status Optional status code to look up
     * @return  string  Error message
     * @static  May be called statically if $status is set
     */
    public function statusDesc($status = null)
    {
        $st = is_null($status) ? $this->status : $status;

        if (isset(self::$_descs[$st])) {
            return self::$_descs[$st];
        }

        return null;
    }

    /**
     * Parse a raw PDU and populate this instance with it's data
     *
     * This function only actually parses the (fixed-length) PDU header.
     * {@link parseParams()} handles the PDU-specific parameter parsing.
     *
     * @param string $pdudata PDU data to parse
     * @see     extractCommand()
     * @see     parseParams()
     */
    public function parse($pdudata)
    {
        /**
         * PDU Format:
         *
         * - Header (16 bytes)
         *   command_length  - 4 bytes
         *   command_id      - 4 bytes
         *   command_status  - 4 bytes
         *   sequence_number - 4 bytes
         * - Body (variable length)
         *   paramater
         *   paramater
         *   ...
         */
        $header = substr($pdudata, 0, 16);
        $this->_length = implode(null, $this->_unpack('N', substr($header, 0, 4)));
        $this->command = self::extractCommand($pdudata);
        $this->status = implode(null, $this->_unpack('N', substr($header, 8, 4)));
        $this->sequence = implode(null, $this->_unpack('N', substr($header, 12, 4)));

        // Parse the rest.
        if (strlen($pdudata) > 16) {
            $this->parseParams(substr($pdudata, 16));
            $this->decode();
        }
        return true;
    }

    /**
     * unpack() signednedd kludge
     *
     * PHP & unpack() have problems with unsigned ints; if the high bit is set
     * in an unsigned int returned from unpack(), the int is treated as signed,
     * even if we requested that it be treated as unsigned.
     *
     * This function checks for the high bit, and if it's set, shifts it off and
     * sets it back with the & operator. It works around the problem on 32-bit
     * platforms, and doesn't break on 64-bit systems. It will probably break
     * in some cases on 8- or 16-bit, but we really don't care about those.
     *
     * @param string $format
     * @param string $data
     * @return  array
     * @link    http://atomized.org/2005/04/phps-integer-oddities/
     * @access  private
     */
    public static function _unpack($format, $data)
    {
        $bin = @unpack($format, $data);
        if (!is_array($bin)) {
            throw new Exception('Can not unpack data');
        }
        $val = array_values($bin);

        if ($format == 'N' && $val[0] < 0) {
            $val[0] = $val[0] << 1 >> 1;
            $val[0] += 0x80000000;
        }

        return $val;
    }

    /**
     * Extract the command from a PDU
     *
     * @param string $pdu Binary PDU data
     * @return  string  PDU command string
     */
    public static function extractCommand($pdu)
    {
        if (strlen($pdu) < 4) {
            return false;
        }
        $intcmd = self::_unpack('N', substr($pdu, 4, 4));
        $intcmd = $intcmd[0];

        return Command::commandName($intcmd);
    }

    /**
     * Generate the raw PDU to send to the remote system
     *
     * @return  string  PDU data
     */
    public function generate()
    {
        // Generate the body
        $body = $this->generateParams();

        // Generate the header
        $this->_length = strlen($body) + 16;

        $header =
            pack('N', $this->_length) . pack('N', Command::commandCode($this->command)) . pack('N', $this->status) .
            pack('N', $this->sequence);

        return $header . $body;
    }

    public function length()
    {
        return $this->_length;
    }

    public function setSequence($sequence)
    {
        $this->sequence = (int)$sequence;

        return $this->sequence;
    }
}

?>
