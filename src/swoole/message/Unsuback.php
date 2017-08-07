<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/3
 * Time: 下午8:27
 */

namespace mqttclient\src\swoole\message;


use mqttclient\src\consts\MessageType;
use mqttclient\src\swoole\Util;

class Unsuback extends BaseMessage
{

    protected $type = MessageType::UNSUBACK;

    protected $need_message_id = true;

    public function decodeVariableHeader($data, &$pos)
    {
        $message_id = Util::decodeUnsignedShort($data,$pos);
        $this->setMessageId($message_id);
        return true;
    }
}