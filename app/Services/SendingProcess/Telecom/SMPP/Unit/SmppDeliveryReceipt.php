<?php

namespace App\Services\SendingProcess\Telecom\SMPP\Unit;

use ReflectionClass;
use ReflectionException;

/**
 * An extension of a SMS, with data embedded into the message part of the SMS.
 * @author hd@onlinecity.dk
 */
class SmppDeliveryReceipt extends SmppSms
{
    public $id;
    public $sub;
    public $dlvrd;
    public $submitDate;
    public $doneDate;
    public $stat;
    public $err;
    public $imsi;
    public $msc;
    public $mccmnc;
    public $text;

    /**
     * Parse a delivery receipt formatted as specified in SMPP v3.4 - Appendix B
     * It accepts all chars except space as the message id
     *
     * @throws InvalidArgumentException
     */
    public function parseDeliveryReceipt()
    {
        $keyWordsArray = [
            'id' => ['key' => 'id:', 'regex' => '([a-f0-9\-\.]+)'],
            'sub' => ['key' => 'sub:', 'regex' => '(\d+)'],
            'dlvrd' => ['key' => 'dlvrd:', 'regex' => '(\d+)'],
            'submitDate' => ['key' => 'submit\sdate:', 'regex' => '(\d+)'],
            'doneDate' => ['key' => 'done\sdate:', 'regex' => '(\d+)'],
            'stat' => ['key' => 'stat:', 'regex' => '([A-Z]+)'],
            'err' => ['key' => 'err:', 'regex' => '(\d+)'],
            'imsi' => ['key' => 'imsi:', 'regex' => '(\d+)'],
            'msc' => ['key' => 'msc:', 'regex' => '([^\s]+)'],
            'mccmnc' => ['key' => 'mccmnc:', 'regex' => '(\d+)'],
            'text' => ['key' => 'text:', 'regex' => '(.*)'],
        ];

        foreach ($keyWordsArray as $param => $valArr) {
            $key = $valArr['key'];
            $regex = $valArr['regex'];
            preg_match("/$key$regex/si", $this->message, $matches);
            if (empty($matches)) {
                continue;
            }
            $this->$param = $matches[1];
            if ($param == 'text') {
                $this->$param = @utf8_decode(utf8_encode($this->$param));
            }
        }

        // Convert dates
        if (!empty($this->submitDate)) {
            $dp = str_split($this->submitDate, 2);
            $this->submitDate = gmmktime($dp[3], $dp[4], isset($dp[5]) ? $dp[5] : 0, $dp[1], $dp[2], $dp[0]);
        }
        if (!empty($this->doneDate)) {
            $dp = str_split($this->doneDate, 2);
            $this->doneDate = gmmktime($dp[3], $dp[4], isset($dp[5]) ? $dp[5] : 0, $dp[1], $dp[2], $dp[0]);
        }
    }

    /**
     * Return this class properties as array, for more functionality (like json_encode).
     *
     * @return array
     * @throws ReflectionException
     */
    public function getClassPropertiesArray()
    {
        $arr = [];
        $refClass = new ReflectionClass($this);
        foreach ($refClass->getProperties() as $property) {
            $name = $property->name;
            if ($property->class == $refClass->name)
                $arr[$name] = $this->$name;
        }

        return $arr;
    }

}
