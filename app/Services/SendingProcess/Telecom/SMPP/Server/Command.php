<?php

namespace App\Services\SendingProcess\Telecom\SMPP\Server;

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

use Exception;

/**
 * SmppServer Command class and data
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
 * @copyright  2005 WebSprockets, LLC.
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @version    CVS:     $Revision: 298434 $
 * @since      Release 0.0.1dev2
 * @link       http://pear.php.net/package/Net_SMPP
 */

// Place includes, constant defines and $_GLOBAL settings here.

// These are the keyvalues for these optional paramaters

/**
 * SMPP v3.4 TON (Type-of-Number) values
 *
 * @see  Server_SMPP_Command_submit_sm::$source_addr_ton
 */
define('SERVER_SMPP_TON_UNK', 0x00); // Unknown
define('SERVER_SMPP_TON_INTL', 0x01); // International
define('SERVER_SMPP_TON_NATNL', 0x02); // National
define('SERVER_SMPP_TON_NWSPEC', 0x03); // Network-specific
define('SERVER_SMPP_TON_SBSCR', 0x04); // Subscriber number
define('SERVER_SMPP_TON_ALNUM', 0x05); // Alphanumberic
define('SERVER_SMPP_TON_ABBREV', 0x06); // Abbreviated

/**
 * SMPP v3.4 NPI (Numbering Plan Indicator) values
 *
 * @see  Net_SMPP_Command_submit_sm::$source_addr_npi
 */
define('SERVER_SMPP_NPI_UNK', 0x00); // Unknown
define('SERVER_SMPP_NPI_ISDN', 0x01); // ISDN (E163/E164)
define('SERVER_SMPP_NPI_DATA', 0x03); // Data (X.121)
define('SERVER_SMPP_NPI_TELEX', 0x04); // Telex (F.69)
define('SERVER_SMPP_NPI_LNDMBL', 0x06); // Land Mobile (E.212)
define('SERVER_SMPP_NPI_NATNL', 0x08); // National
define('SERVER_SMPP_NPI_PRVT', 0x09); // Private
define('SERVER_SMPP_NPI_ERMES', 0x0A); // ERMES
define('SERVER_SMPP_NPI_IP', 0x0E); // IPv4
define('SERVER_SMPP_NPI_WAP', 0x12); // WAP

/**
 * SMPP v3.4 encoding types
 *
 * @see  Net_SMPP_Command_submit_sm::$data_coding
 */
define('SERVER_SMPP_ENCODING_DEFAULT', 0x00); // SMSC Default Alphabet
define('SERVER_SMPP_ENCODING_IA5', 0x01); // IA5 (CCITT T.50)/ASCII (ANSI X3.4)
define('SERVER_SMPP_ENCODING_BINARY', 0x02); // Octet unspecified (8-bit binary)
define('SERVER_SMPP_ENCODING_ISO88591', 0x03); // Latin 1 (ISO-8859-1)
define('SERVER_SMPP_ENCODING_BINARY2', 0x04); // Octet unspecified (8-bit binary)
define('SERVER_SMPP_ENCODING_JIS', 0x05); // JIS (X 0208-1990)
define('SERVER_SMPP_ENCODING_ISO88595', 0x06); // Cyrllic (ISO-8859-5)
define('SERVER_SMPP_ENCODING_ISO88598', 0x07); // Latin/Hebrew (ISO-8859-8)
define('SERVER_SMPP_ENCODING_ISO10646', 0x08); // UCS2 (ISO/IEC-10646)
define('SERVER_SMPP_ENCODING_PICTOGRAM', 0x09); // Pictogram Encoding
define('SERVER_SMPP_ENCODING_ISO2022JP', 0x0A); // ISO-2022-JP (Music Codes)
define('SERVER_SMPP_ENCODING_EXTJIS', 0x0D); // Extended Kanji JIS(X 0212-1990)
define('SERVER_SMPP_ENCODING_KSC5601', 0x0E); // KS C 5601

/**
 * SMPP v3.4 langauge types
 *
 * @see  Net_SMPP_Command_submit_sm::$language_indicator
 */
define('SERVER_SMPP_LANG_DEFAULT', 0x00);
define('SERVER_SMPP_LANG_EN', 0x01);
define('SERVER_SMPP_LANG_FR', 0x02);
define('SERVER_SMPP_LANG_ES', 0x03);
define('SERVER_SMPP_LANG_DE', 0x04);

/**
 * SMPP v3.4 esm_class values
 *
 * @see  Net_SMPP_Command_submit_sm::$esm_class
 */
// Default SMSC Mode (e.g. Store and Forward)
define('SERVER_SMPP_MSGMODE_DEFAULT', 0x00);
// Datagram mode
define('SERVER_SMPP_MSGMODE_DATAGRAM', 0x01);
// Forward (i.e. Transaction) mode
define('SERVER_SMPP_MSGMODE_FORWARD', 0x02);
// Store and Forward mode (use to select Store and Forward mode if Default
// SMSC Mode is non Store and Forward)
define('SERVER_SMPP_MSGMODE_STOREFORWARD', 0x03);

// Default message Type (i.e. normal message)
define('SERVER_SMPP_MSGTYPE_DEFAULT', 0x00);
// Short Message contains ESME Delivery Acknowledgement
define('SERVER_SMPP_MSGTYPE_DELIVERYACK', 0x08);
// Short Message contains ESME Manual/User Acknowledgement
define('SERVER_SMPP_MSGTYPE_USERACK', 0x10);

// No specific features selected
define('SERVER_SMPP_GSMFEAT_NONE', 0x00);
// UDHI Indicator (only relevant for MT short messages)
define('SERVER_SMPP_GSMFEAT_UDHI', 0x40);
// Set Reply Path (only relevant for GSM network)
define('SERVER_SMPP_GSMFEAT_REPLYPATH', 0x80);
// Set UDHI and Reply Path (only relevant for GSM network)
define('SERVER_SMPP_GSMFEAT_UDHIREPLYPATH', 0xC0);

/**
 * Base Net_SMPP PDU command class
 *
 * This class is the base from which the command-specific classes inherit.
 * It contains functions common to the parsing and generation of the paramaters
 * for all SMPP commands.
 *
 * @category   Networking
 * @package    SmppServer
 * @author     Ian Eure <ieure@php.net>
 * @copyright  2005 WebSprockets, LLC.
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @version    CVS:     $Revision: 298434 $
 * @since      Release 0.0.1dev2
 * @link       http://pear.php.net/package/Net_SMPP
 */
class Command extends PDU
{
    protected static $_optionalParams = array(
        'dest_addr_subunit' => 0x0005,
        'dest_network_type' => 0x0006,
        'dest_bearer_type' => 0x0007,
        'dest_telematics_id' => 0x0008,
        'source_addr_subunit' => 0x000D,
        'source_network_type' => 0x000E,
        'source_bearer_type' => 0x000F,
        'source_telematics_id' => 0x0010,
        'qos_time_to_live' => 0x0017,
        'payload_type' => 0x0019,
        'additional_status_info_text' => 0x001D,
        'receipted_message_id' => 0x001E,
        'ms_msg_wait_facilities' => 0x0030,
        'privacy_indicator' => 0x0201,
        'source_subaddress' => 0x0202,
        'dest_subaddress' => 0x0203,
        'user_message_reference' => 0x0204,
        'user_response_code' => 0x0205,
        'source_port' => 0x020A,
        'destination_port' => 0x020B,
        'sar_msg_ref_num' => 0x020C,
        'language_indicator' => 0x020D,
        'sar_total_segments' => 0x020E,
        'sar_segment_seqnum' => 0x020F,
        'sc_interface_version' => 0x0210,
        'callback_num_pres_ind' => 0x0302,
        'callback_num_atag' => 0x0303,
        'number_of_messages' => 0x0304,
        'callback_num' => 0x0381,
        'dpf_result' => 0x0420,
        'set_dpf' => 0x0421,
        'ms_availability_status' => 0x0422,
        'network_error_code' => 0x0423,
        'message_payload' => 0x0424,
        'delivery_failure_reason' => 0x0425,
        'more_messages_to_send' => 0x0426,
        'message_state' => 0x0427,
        'ussd_service_op' => 0x0501,
        'display_time' => 0x1201,
        'sms_signal' => 0x1203,
        'ms_validity' => 0x1204,
        'alert_on_message_delivery' => 0x130C,
        'its_reply_type' => 0x1380,
        'its_session_info' => 0x1383
    );
    protected static $_commandList = array(
        'generic_nack' => 0x80000000,
        'bind_receiver' => 0x00000001,
        'bind_receiver_resp' => 0x80000001,
        'bind_transmitter' => 0x00000002,
        'bind_transmitter_resp' => 0x80000002,
        'query_sm' => 0x00000003,
        'query_sm_resp' => 0x80000003,
        'submit_sm' => 0x00000004,
        'submit_sm_resp' => 0x80000004,
        'deliver_sm' => 0x00000005,
        'deliver_sm_resp' => 0x80000005,
        'unbind' => 0x00000006,
        'unbind_resp' => 0x80000006,
        'replace_sm' => 0x00000007,
        'replace_sm_resp' => 0x80000007,
        'cancel_sm' => 0x00000008,
        'cancel_sm_resp' => 0x80000008,
        'bind_transceiver' => 0x00000009,
        'bind_transceiver_resp' => 0x80000009,
        'outbind' => 0x0000000B,
        'enquire_link' => 0x00000015,
        'enquire_link_resp' => 0x80000015,
        'submit_multi' => 0x00000021,
        'submit_multi_resp' => 0x80000021,
        'alert_notification' => 0x00000102,
        'data_sm' => 0x00000103,
        'data_sm_resp' => 0x80000103
    );
    /**
     * Parameter defs for this command
     *
     * *ORDER IS SIGNIFICANT!* Required paramaters *MUST* be listed in the
     * order as defined by the protocol. Optional paramaters *MUST* come
     * after all the required paramaters.
     *
     * This should look like:
     *
     * $_defs = array(
     *     'field_name' => array(
     *         'type' => 'field_type',
     *         'size' => field_size,
     *         'max'  => field_max_size
     *     )
     *  );
     *
     * 'type' is one of: int, string, ostring.
     * - int: your basic integer. 'size' is the number of bytes the value
     *        will be packed into. 'max' is ignored.
     * - string: a basic string. 'size' is the size of the string in bytes
     *           if the length of the string is less than 'size,' it will be
     *           null-padded. 'max' is the maximum length for variable-length
     *           strings. Only set one of 'max' or 'size.'
     * - ostring: Non-null-terminated, variable length octet string.
     *
     * @var     array
     * @access  protected
     */
    var $_defs = array();

    /**
     *
     * @param array $args Values to set
     * @return  void
     * @see     set()
     */
    public function __construct($command, $args = array(), $status = SERVER_SMPP_ESME_ROK)
    {
        $this->command = strtolower($command);
        if (!empty($args['sequence'])) {
            $this->sequence = $args['sequence'];
        } else {
            $this->sequence = null;
        }
        $this->status = $status;

        $this->set($args);
    }

    /**
     * Set values in this object
     *
     * Unknown values are ignored.
     *
     * @param array $args Values to set
     * @return  void
     */
    public function set($args = array())
    {
        foreach ($args as $k => $v) {
            if ($this->fieldExists($k)) {
                $this->$k = $v;
            }
        }
    }

    /**
     * Does this field exist?
     *
     * @param string $field Field to check
     * @return  boolean  true if it exists, false otherwise
     * @access  protected
     */
    public function fieldExists($field)
    {
        return isset($this->_defs[$field]);
    }

    /**
     * Get the name of a command from it's ID
     *
     * @param int $cmdcode Command ID
     * @return  mixed  String command name, or false
     */
    public static function commandName($cmdcode)
    {
        if (in_array($cmdcode, self::$_commandList)) {
            return array_search($cmdcode, self::$_commandList);
        }
        return false;
    }

    /**
     * Get the ID of a command from it's name
     *
     * @param int $cmdname Command name
     * @return  mixed  Int command ID, or false
     */
    public static function commandCode($cmdname)
    {
        $cmdname = strtolower($cmdname);
        if (isset(self::$_commandList[$cmdname])) {
            return self::$_commandList[$cmdname];
        }
        return false;
    }

    /**
     * Generate the binary data from the object
     *
     * This is the workhorse of this class (and all the other
     * ServerCommand* classes). It's responsible for generating the binary
     * protocol data from the fields in the object, and is the opposite of
     * parse().
     *
     * @return  string
     * @see     _packFormat()
     */
    public function generateParams()
    {
        // Is there a prep() method?
        if (method_exists($this, 'prep')) {
            $this->prep();
        }

        $body = '';
        foreach ($this->_defs as $field => $def) {
            if ($this->fieldIsOptional($field)) {
                if (!isset($this->$field)) {
                    continue;
                }
                $body .= $this->_generateOptHeader($field);
            }

            switch ($def['type']) {
                case 'int':
                    $body .= $this->_generateInt($field);
                    break;

                case 'string':
                    $body .= $this->_generateString($field);
                    break;

                case 'ostring':
                    $body .= $this->_generateOString($field);
                    break;
            }
        }
        return $body;
    }

    /**
     * Is this field optional?
     *
     * @param string $field Field name
     * @return  boolean  true if optional, false otherwise
     */
    public function fieldIsOptional($field)
    {
        if (array_key_exists($field, self::$_optionalParams)) {
            return true;
        }

        return false;
    }

    /**
     * Generate a header for an optional param
     *
     * @param string $field Paramater to generate header for
     * @return  string  Binary header representation
     * @access  private
     */
    protected function _generateOptHeader($field)
    {
        if (isset($this->_defs[$field]['size'])) {
            $len = $this->_defs[$field]['size'];
        } else if ($this->_defs[$field]['type'] == 'ostring') {
            $len = strlen($this->$field);
        } else {
            $len = strlen($this->$field) + 1;
        }

        // Generate the binary data - field ID first, then field len
        return pack('n', self::$_optionalParams[$field]) .
            pack('n', $len);
    }

    /**
     * Generate an integer value
     *
     * @param string $field Paramater to generate
     * @return  string  Binary representation
     * @access  private
     */
    protected function _generateInt($field)
    {
        // https://www.php.net/manual/en/function.pack.php
        // >= 8.0.0	This function no longer returns false on failure.
        try {
            $pf = $this->_packFormat($field);
            return pack($pf, $this->$field);
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Get the format argument for pack()
     *
     * @param string   Field to get pack argument for
     * @return  string   Pack format for $field
     * @access  private
     */
    protected function _packFormat($field)
    {
        return match ($this->_defs[$field]['size']) {
            1 => 'C',
            2 => 'n',
            3 => 'N',
            default => null,
        };
    }

    /**
     * Generate a string value
     *
     * @param string $field Paramater to generate
     * @return  string  Binary string representation
     * @access  private
     */
    protected function _generateString($field)
    {
        if (strlen($this->$field) == 0) {
            return chr(0);
        }

        if (isset($this->_defs[$field]['size'])) {

            // Fixed-length - NULL pad
            $this->field = substr($this->$field, 0, $this->_defs[$field]['size'] - 1);
            $val = str_pad($this->$field, $this->_defs[$field]['size'], chr(0));

        } else if (isset($this->_defs[$field]['max'])) {

            if (strlen($this->$field) >= $this->_defs[$field]['max']) {
                // FIXME - add warning.
                $this->field = substr($this->$field, 0, $this->_defs[$field]['max'] - 1);
            }

            $val = $this->$field . chr(0);
        }
        return $val;
    }

    /**
     * Generate an octet string value
     *
     * Octet strings do not have a null terminator
     *
     * @param string $field Paramater to generate
     * @return  string  Binary string representation
     * @access  private
     */
    protected function _generateOString($field)
    {
        return $this->$field;
    }

    /**
     * Is this a fixed-length field?
     *
     * @param string $field Field to test
     * @return  boolean  true if it is, false otherwise
     * @access  protected
     */
    public function isFixed($field)
    {
        if (isset($this->_def[$field]['size'])) {
            return true;
        }
        return false;
    }

    /**
     * Parse data into the object structure
     *
     * @param string $data Data to parse
     * @return  boolean  true on success, false otherwise
     */
    public function parseParams($data)
    {
        $pos = 0;
        $dl = strlen($data);
        foreach ($this->_defs as $field => $def) {
            // Abort the loop if we're at the end of the data, or if we
            // encounter an optional paramater
            if ($pos == $dl ||
                $this->fieldIsOptional($field)) {
                break;
            }

            switch ($def['type']) {
                case 'int':
                    $this->_parseInt($field, $data, $pos);
                    break;

                case 'string':
                    $this->_parseString($field, $data, $pos);
                    break;

                case 'ostring':
                    $this->_parseOString($field, $data, $pos);
                    break;
            }
        }

        // Are there optional paramaters left?
        if ($pos < $dl) {
            $this->parseOptionalParams(substr($data, $pos));
        }
    }

    /**
     * Parse a fixed-length chunk from a PDU
     *
     * @param string $field Field to put this data in
     * @param string $data Raw PDU data
     * @param int $pos Position data starts in the PDU
     * @return  void
     * @access  private
     */
    protected function _parseInt($field, &$data, &$pos)
    {
        $len = $this->_defs[$field]['size'];
        $this->$field = implode(null,
            $this->_unpack($this->_packFormat($field),
                substr($data, $pos, $len)));
        $pos += $len;
    }

    /**
     * Parse a variable-length string from a PDU
     *
     * @param string $field Field to put this data in
     * @param string $data Raw PDU data
     * @param int $pos Position data starts in the PDU
     * @return  void
     * @todo    Handle GSM char encoding
     * @note    The fixed-length code is probably wrong.
     * @access  private
     */
    protected function _parseString($field, &$data, &$pos)
    {
        $fe = strpos($data, chr(0), $pos);  // End of the string
        $fl = $fe - $pos;                 // String length

        $this->$field = substr($data, $pos, $fl);
        $pos += $fl + 1;                  // Add one for the NULL terminator
    }

    /**
     * Parse an  octet string from a PDU
     *
     * @param string $field Field to put this data in
     * @param string $data Raw PDU data
     * @param int $pos Position data starts in the PDU
     * @param int $len String length or NULL
     * @return  void
     * @todo    Handle GSM char encoding
     * @access  private
     */
    protected function _parseOString($field, &$data, &$pos, $len = null)
    {
        if (is_null($len)) {
            $lenf = $this->_defs[$field]['lenField'];
            $len = $this->$lenf;
        }
        $this->$field = substr($data, $pos, $len);
        $pos += $len;
    }

    /**
     * Parse optional paramaters
     *
     * @param string $data Optional paramaters to parse
     * @return  void
     * @access  protected
     */

    public function parseOptionalParams($data)
    {
        /**
         * Optional params have the `TLV' format:
         *
         * - Type   (2 bytes)
         * - Length (2 bytes)
         * - Value  (variable, `Length' bytes)
         */

        $dl = strlen($data);
        $pos = 0;
        while ($pos < $dl) {

            $type = implode(null, unpack('n', substr($data, $pos, 2)));
            $field = array_search($type, self::$_optionalParams);

            $pos += 2;
            if ($field == null || $field == false) {
                return;
            }

            $len = implode(null, unpack('n', substr($data, $pos, 2)));
            $pos += 2;

            switch ($this->_defs[$field]['type']) {
                case 'int':
                    $this->_parseInt($field, $data, $pos);
                    break;

                case 'string';
                    $this->_parseString($field, $data, $pos);
                    break;

                case 'ostring':
                    $this->_parseOString($field, $data, $pos, $len);
                    break;
            }
        }
    }

    public function decode()
    {
        if (!property_exists($this, 'data_coding')) {
            return false;
        }
        $coding = (int)$this->data_coding;
        $msg = empty($this->sm_length) ? $this->message_payload : $this->short_message;

        if ($this->isUDHI()) {
            $msg = $this->parseUDHI($msg);
        }

        if (empty($coding)) {
            $msg = $this->gsm0338toUTF8($msg);
        }
        if ($coding == SERVER_SMPP_ENCODING_ISO10646) {
            $msg = mb_convert_encoding($msg, 'UTF-8', 'UCS-2');
        }
        if ($coding == SERVER_SMPP_ENCODING_ISO88591) {
            $msg = mb_convert_encoding($msg, 'UTF-8', 'ISO-8859-1');
        }
        if ($coding == SERVER_SMPP_ENCODING_ISO88595) {
            $msg = mb_convert_encoding($msg, 'UTF-8', 'ISO-8859-5');
        }
        if ($coding == SERVER_SMPP_ENCODING_ISO88598) {
            $msg = mb_convert_encoding($msg, 'UTF-8', 'ISO-8859-8');
        }
        if ($coding == SERVER_SMPP_ENCODING_JIS) {
            $msg = mb_convert_encoding($msg, 'UTF-8', 'JIS');
        }
        if ($coding == SERVER_SMPP_ENCODING_ISO2022JP) {
            $msg = mb_convert_encoding($msg, 'UTF-8', 'ISO-2022-JP'); # ISO-2022-JP-KDDI ?
        }

        if (empty($this->sm_length)) {
            $this->message_payload = $msg;
        } else {
            $this->short_message = $msg;
        }

        return true;
    }

    public function isUDHI()
    {
        if (!empty($this->esm_class) && ($this->esm_class & SERVER_SMPP_GSMFEAT_UDHI)) {
            return true;
        }

        return false;
    }

    public function parseUDHI($msg)
    {
        if ($this->isUDHI() && strlen($msg) >= 6) {

            $udhi = unpack('clength/cindicator/cslength/cref/camount/csequence', substr($msg, 0, 6));
            if (empty($udhi) || empty($udhi['length']) || empty($udhi['slength']) || $udhi['length'] != 5 ||
                $udhi['slength'] != 3) {
                return $msg;
            }
            $msg = substr($msg, 6);
            $this->sar_msg_ref_num = (int)$udhi['ref'];
            $this->sar_total_segments = (int)$udhi['amount'];
            $this->sar_segment_seqnum = (int)$udhi['sequence'];
        }

        return $msg;
    }

    public static function gsm0338toUTF8($string)
    {
        $dict = array(
            '@' => "\x00", '£' => "\x01", '$' => "\x02", '¥' => "\x03", 'è' => "\x04", 'é' => "\x05", 'ù' => "\x06",
            'ì' => "\x07", 'ò' => "\x08", 'Ç' => "\x09", 'Ø' => "\x0B", 'ø' => "\x0C", 'Å' => "\x0E", 'å' => "\x0F",
            'Δ' => "\x10", '_' => "\x11", 'Φ' => "\x12", 'Γ' => "\x13", 'Λ' => "\x14", 'Ω' => "\x15", 'Π' => "\x16",
            'Ψ' => "\x17", 'Σ' => "\x18", 'Θ' => "\x19", 'Ξ' => "\x1A", 'Æ' => "\x1C", 'æ' => "\x1D", 'ß' => "\x1E",
            'É' => "\x1F",
            // all \x2? removed
            // all \x3? removed
            // all \x4? removed
            'Ä' => "\x5B", 'Ö' => "\x5C", 'Ñ' => "\x5D", 'Ü' => "\x5E", '§' => "\x5F",
            '¿' => "\x60",
            'ä' => "\x7B", 'ö' => "\x7C", 'ñ' => "\x7D", 'ü' => "\x7E", 'à' => "\x7F",
            '^' => "\x1B\x14", '{' => "\x1B\x28", '}' => "\x1B\x29", '\\' => "\x1B\x2F", '[' => "\x1B\x3C",
            '~' => "\x1B\x3D", ']' => "\x1B\x3E", '|' => "\x1B\x40", '€' => "\x1B\x65"
        );
        return strtr($string, array_flip($dict));
    }
}
