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

class Pubrel extends BaseMessage
{

    protected $type = MessageType::PUBREL;

    /*
     * MQTT-3.6.1-1
    * PUBREL控制报文固定报头的第3,2,1,0位是保留位,必须被设置为0,0,1,0。
    * 服务端必须将其它的任何值都当做是不合法的并关闭网络连接。
    */
    protected $reserved_flags = 0x02;

    protected $need_message_id = true;

    public function decodeVariableHeader($data, &$pos)
    {
        $message_id = Util::decodeUnsignedShort($data,$pos);
        $this->setMessageId($message_id);
        return true;
    }
}