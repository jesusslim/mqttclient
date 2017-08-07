<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/3
 * Time: 下午8:25
 */

namespace mqttclient\src\swoole\message;


use mqttclient\src\consts\MessageType;

class Connack extends BaseMessage
{

    protected $type = MessageType::CONNACK;

}