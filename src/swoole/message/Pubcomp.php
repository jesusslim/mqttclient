<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/3
 * Time: 下午8:26
 */

namespace mqttclient\src\swoole\message;


use mqttclient\src\consts\MessageType;
use mqttclient\src\swoole\Util;

class Pubcomp extends BaseMessage
{

    protected $type = MessageType::PUBCOMP;

    protected $need_message_id = true;

    public function decodeVariableHeader($data, &$pos)
    {
        $message_id = Util::decodeUnsignedShort($data,$pos);
        $this->setMessageId($message_id);
        return true;
    }

}