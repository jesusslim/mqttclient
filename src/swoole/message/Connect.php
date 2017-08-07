<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/3
 * Time: 下午8:25
 */

namespace mqttclient\src\swoole\message;


use mqttclient\src\consts\MessageType;
use mqttclient\src\consts\MqttVersion;
use mqttclient\src\swoole\Util;

class Connect extends BaseMessage
{

    protected $type = MessageType::CONNECT;

    public function getPayload()
    {
        $payload = '';
        $payload .= Util::packLenStr($this->getClient()->getClientId());

        if ($this->getClient()->getWill()){
            $payload .= Util::packLenStr($this->getClient()->getWill()->getTopic());
            $payload .= Util::packLenStr($this->getClient()->getWill()->getMessage());
        }

        if ($this->getClient()->getAuth()){
            $auth = $this->getClient()->getAuth();
            if ($auth['user_name']) $payload .= Util::packLenStr($auth['user_name']);
            if ($auth['password']) $payload .= Util::packLenStr($auth['password']);
        }

        return $payload;
    }

    public function encodeVariableHeader()
    {
        $buffer = '';

        //version
        $buffer .= Util::packLenStr($this->getClient()->getMqttVersion() == MqttVersion::V3 ? 'MQIsdp' : 'MQTT');

        //level
        $buffer .= chr($this->getClient()->getMqttVersion());

        //flag
        $flag = 0;

        $auth = $this->getClient()->getAuth();
        if ($auth && $auth['user_name']){
            $flag |= 0x80;
        }
        if ($auth && $auth['password']){
            $flag |= 0x40;
        }

        $will = $this->getClient()->getWill();
        if ($will){
            if ($will->getRetain()){
                $flag |= 0x20;
            }
            $flag += $will->getQos() << 3;
            $flag |= 0x04;
        }

        if ($this->getClient()->getClean()){
            $flag |= 0x02;
        }

        $buffer .= chr($flag);

        $buffer .= pack('n', $this->getClient()->getKeepAlive());

        return $buffer;
    }

    public function decodeVariableHeader($data,&$pos)
    {
        //connect 客户端 不需要解析
        return null;
    }
}