<?php

namespace App\Services\SendingProcess\Telecom\SMPP\Unit;

use App\Services\SendingProcess\Telecom\SMPP\SmppAddress;

/**
 * Primitive type to represent SMSes
 * @author hd@onlinecity.dk
 */
class SmppSms extends SmppPdu
{
    /**
     * Construct a new SMS
     *
     * @param integer $id
     * @param integer $status
     * @param integer $sequence
     * @param string $body
     * @param string $service_type
     * @param Address $source
     * @param Address $destination
     * @param integer $esmClass
     * @param integer $protocolId
     * @param integer $priorityFlag
     * @param integer $registeredDelivery
     * @param integer $dataCoding
     * @param string $message
     * @param array $tags (optional)
     * @param string $scheduleDeliveryTime (optional)
     * @param string $validityPeriod (optional)
     * @param integer $smDefaultMsgId (optional)
     * @param integer $replaceIfPresentFlag (optional)
     */
    public function __construct($id, $status, $sequence, $body, public $service_type, public SmppAddress $source,
                                public SmppAddress $destination,
                                public $esmClass, public $protocolId, public $priorityFlag, public $registeredDelivery,
                                public $dataCoding, public $message, public $tags,
                                public $scheduleDeliveryTime = null, public $validityPeriod = null,
                                public $smDefaultMsgId = null, public $replaceIfPresentFlag = null)
    {
        parent::__construct($id, $status, $sequence, $body);
    }

}
