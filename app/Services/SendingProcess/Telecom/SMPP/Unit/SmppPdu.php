<?php

namespace App\Services\SendingProcess\Telecom\SMPP\Unit;

use App\Services\SendingProcess\Telecom\SMPP\SMPP;

/**
 * Primitive class for encapsulating PDUs
 * @author hd@onlinecity.dk
 */
class SmppPdu
{
    public $length;
    public $commandId;

    /**
     * Create new generic PDU object
     *
     * @param integer $id
     * @param integer $status
     * @param integer $sequence
     * @param string $body
     * @param         $length
     * @param         $bufLength
     * @param         $bufHeaders
     * @param         $commandId
     */
    public function __construct(public $id, public $status, public $sequence, public $body, $length = null,
                                public $bufLength = null, public $bufHeaders = null, $commandId = null)
    {
//		echo "PDU Body: $body\n";
    }

    public function getReadPduOutput()
    {
        $str = "Read PDU         : $this->length bytes";
        $str .= ' ' . chunk_split(bin2hex($this->bufLength . $this->bufHeaders . $this->body), 2, " ");
        $str .= " command id      : 0x" . dechex($this->commandId);
        $str .= " command status  : 0x" . dechex($this->status) . " " . SMPP::getStatusMessage($this->status);
        $str .= ' sequence number : ' . $this->sequence;

        return $str;
    }
}
