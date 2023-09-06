<?php

namespace App\Services\SendingProcess\Telecom\SMPP\Server;

use App\Services\SendingProcess\Telecom\SMPP\SmppServer;
use React\Socket\ConnectionInterface;
use React\Socket\LimitingServer;

class Session
{
    protected $_sesions = [];
    protected $_users = [];

    public function init()
    {
        $this->_sesions = [];
        $this->_users = [];
    }

    public function create(LimitingServer $server, ConnectionInterface $connection)
    {
        $url = $connection->getRemoteAddress();
        $hash = md5($server->getAddress() . '-' . $url . '-' . time());
        $ip = parse_url($url);

        $this->_sesions[$hash] = array(
            'created' => microtime(true),
            'last_data' => microtime(true),
            'user' => null,
            'sequence' => 0,
            'balance' => null,
            'can_send' => false,
            'tail' => '',
            'url' => $url,
            'ip' => (empty($ip['host']) ? $url : $ip['host']),
            'port' => (empty($ip['port']) ? 0 : $ip['port']),
            'bind' => SmppServer::STATE_OPEN,
            'queue' => [],
        );

        return $hash;
    }

    public function get($hash)
    {
        if (empty($this->_sesions[$hash])) {
            return null;
        }
        return $this->_sesions[$hash];
    }

    public function getHashByUrl($url)
    {
        if (!empty($this->_sesions)) {
            foreach ($this->_sesions as $hash => $bind) {
                if ($bind['url'] == $url) {
                    return $hash;
                }
            }
        }

        return null;
    }

    public function last($hash)
    {
        if (empty($this->_sesions[$hash])) {
            return null;
        }
        return $this->_sesions[$hash]['last_data'] = microtime(true);
    }

    public function getLast($hash)
    {
        if (empty($this->_sesions[$hash]['last_data'])) {
            return 0;
        }
        return $this->_sesions[$hash]['last_data'];
    }

    public function authorized($hash)
    {
        return !empty($this->_sesions[$hash]['user']);
    }

    public function nextSequence($hash)
    {
        if (empty($this->_sesions[$hash])) {
            return null;
        }
        return ++$this->_sesions[$hash]['sequence'];
    }

    public function user($login)
    {
//        $login = trim($login);
//        $login = strtolower($login);
        $login = intval($login);

        if (empty($this->_users['id' . $login])) {
            return null;
        }

        return $this->_users['id' . $login];
    }

    /**
     * @param $hash
     * @param $pdu
     * @return PDU
     */
    public function getPdu($hash, $pdu)
    {
        if (empty($pdu->sequence)) {
            return null;
        }
        if (empty($this->_sesions[$hash]['queue']['id' . $pdu->sequence])) {
            return null;
        }
        return $this->_sesions[$hash]['queue']['id' . $pdu->sequence];
    }

    public function getDeliverSm($hash)
    {
        $dlrs = [];

        if (!empty($this->_sesions[$hash]['queue'])) {
            foreach ($this->_sesions[$hash]['queue'] as $pdu) {
                if ($pdu['p']->command == 'deliver_sm') {
                    $dlrs[$pdu['p']->receipted_message_id] = $pdu;
                }
            }
        }

        return $dlrs;
    }

    public function setPdu($hash, $pdu)
    {
        if (empty($pdu->sequence)) {
            return false;
        }
        if (empty($this->_sesions[$hash])) {
            return false;
        }
        if (empty($this->_sesions[$hash]['queue']['id' . $pdu->sequence])) {
            $this->_sesions[$hash]['queue']['id' . $pdu->sequence] = ['t' => microtime(true), 'p' => $pdu];
            return true;
        }

        return false;
    }

    public function delPdu($hash, $pdu)
    {
        if (empty($pdu->sequence)) {
            return false;
        }
        if (empty($this->_sesions[$hash])) {
            return false;
        }
        if (!empty($this->_sesions[$hash]['queue']['id' . $pdu->sequence])) {
            unset($this->_sesions[$hash]['queue']['id' . $pdu->sequence]);
            return true;
        }

        return false;
    }

    public function bind($bind, $hash, $login)
    {
//        $login = trim($login);
//        $login = strtolower($login);
        $login = intval($login);

        if (empty($this->_sesions[$hash])) {
            return false;
        }

        $this->_sesions[$hash]['user'] = $login;
        $this->_sesions[$hash]['bind'] = $bind;
        $this->_users['id' . $login][$hash] = $bind;

        return $this->userBinds($login, $bind);
    }

    public function userBinds($login, $bind = null)
    {
//        $login = trim($login);
//        $login = strtolower($login);
        $login = intval($login);

        if (!empty($bind)) {
            $c = 0;
            if (!empty($this->_users['id' . $login])) {
                foreach ($this->_users['id' . $login] as $b) {
                    if ($b === $bind) {
                        $c++;
                    }
                }
            }
            return $c;
        }

        return count($this->_users['id' . $login]);
    }

    public function close($hash)
    {
        if (empty($this->_sesions[$hash])) {
            unset($this->_sesions[$hash]);
            return true;
        }

        $login = $this->_sesions[$hash]['user'];
        if (!empty($login)) {
            unset($this->_users['id' . $login][$hash]);
            if (empty($this->_users['id' . $login])) {
                unset($this->_users['id' . $login]);
            }
        }
        unset($this->_sesions[$hash]);

        return true;
    }

    public function total()
    {
        return count($this->_sesions);
    }

    public function queue($hash, $command = null)
    {
        if (empty($this->_sesions[$hash])) {
            return 0;
        }

        if (!empty($command)) {
            $c = 0;
            if (!empty($this->_sesions[$hash]['queue'])) {
                foreach ($this->_sesions[$hash]['queue'] as $pdu) {
                    if ($pdu['p']->command == $command) {
                        $c++;
                    }
                }
            }
            return $c;
        }

        return count($this->_sesions[$hash]['queue']);
    }

    public function getQueue($hash)
    {
        if (empty($this->_sesions[$hash])) {
            return [];
        }
        return $this->_sesions[$hash]['queue'];
    }

    public function stat()
    {
        $u_binds = [];
        if (!empty($this->_users)) {
            foreach ($this->_users as $user) {
                foreach ($user as $b) {
                    if (empty($u_binds[$b])) {
                        $u_binds[$b] = 1;
                    } else {
                        $u_binds[$b]++;
                    }
                }
            }
        }
        $s_binds = [];
        $s_queues = [];
        if (!empty($this->_sesions)) {
            foreach ($this->_sesions as $bind) {
                if (!empty($bind['queue'])) {
                    foreach ($bind['queue'] as $pdu) {
                        if (empty($s_queues[$pdu['p']->command])) {
                            $s_queues[$pdu['p']->command] = 1;
                        } else {
                            $s_queues[$pdu['p']->command]++;
                        }
                    }
                }
                if (empty($s_binds[$bind['bind']])) {
                    $s_binds[$bind['bind']] = 1;
                } else {
                    $s_binds[$bind['bind']]++;
                }
            }
        }

        $binds = [];
        $diff = [];

        if (!empty($s_binds)) {
            foreach ($s_binds as $k => $v) {

                $mm = array_key_exists($k, $u_binds) ? $u_binds[$k] : null;

                if ($k == SmppServer::STATE_BOUND_TX) {
                    $binds['tx'] = $v;
                    if ($mm != $v) {
                        $diff['tx'] = $mm;
                    }
                } elseif ($k == SmppServer::STATE_BOUND_RX) {
                    $binds['rx'] = $v;
                    if ($mm != $v) {
                        $diff['rx'] = $mm;
                    }
                } elseif ($k == SmppServer::STATE_BOUND_TRX) {
                    $binds['trx'] = $v;
                    if ($mm != $v) {
                        $diff['trx'] = $mm;
                    }
                } else {
                    $binds[$k] = $v;
                    if ($mm != $v) {
                        $diff[$k] = $mm;
                    }
                }
            }
        }

        if (!empty($u_binds)) {
            foreach ($u_binds as $k => $v) {

                $mm = array_key_exists($k, $s_binds) ? $s_binds[$k] : null;

                if ($k == SmppServer::STATE_BOUND_TX) {
                    $binds['tx'] = $v;
                    if ($mm != $v) {
                        $diff['tx'] = $mm;
                    }
                } elseif ($k == SmppServer::STATE_BOUND_RX) {
                    $binds['rx'] = $v;
                    if ($mm != $v) {
                        $diff['rx'] = $mm;
                    }
                } elseif ($k == SmppServer::STATE_BOUND_TRX) {
                    $binds['trx'] = $v;
                    if ($mm != $v) {
                        $diff['trx'] = $mm;
                    }
                } else {
                    $binds[$k] = $v;
                    if ($mm != $v) {
                        $diff[$k] = $mm;
                    }
                }
            }
        }

        return ['total_users' => count($this->_users), 'total_binds' => count($this->_sesions),
            'user_binds' => $u_binds, 'session_binds' => $s_binds, 'queues' => $s_queues, 'binds' => $binds,
            'diff' => $diff];
    }

    public function sendDlr($hash)
    {
        if (empty($this->_sesions[$hash]) || empty($this->_sesions[$hash]['user'])) {
            return false;
        }

        if ($this->_sesions[$hash]['bind'] == SmppServer::STATE_BOUND_RX) {
            return true;
        }
        if ($this->_sesions[$hash]['bind'] == SmppServer::STATE_BOUND_TRX) {
            return true;
        }

        return false;
    }

    public function balance($hash, $balance)
    {
        if (empty($this->_sesions[$hash])) {
            return null;
        }
        return $this->_sesions[$hash]['balance'] = (float)$balance;
    }

    public function canSend($hash, $can)
    {
        if (empty($this->_sesions[$hash])) {
            return null;
        }
        return $this->_sesions[$hash]['can_send'] = boolval($can);
    }

    public function setTail($hash, $data)
    {
        if (empty($this->_sesions[$hash])) {
            return null;
        }
        return $this->_sesions[$hash]['tail'] = $data;
    }

    public function getTail($hash)
    {
        if (empty($this->_sesions[$hash])) {
            return null;
        }
        return $this->_sesions[$hash]['tail'];
    }
}