<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/3
 * Time: 下午8:28
 */

namespace mqttclient\src\swoole\message;


use mqttclient\src\consts\MessageType;

class Disconnect extends BaseMessage
{

    protected $type = MessageType::DISCONNECT;

}