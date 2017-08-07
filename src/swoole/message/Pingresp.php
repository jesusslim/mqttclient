<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/3
 * Time: 下午8:27
 */

namespace mqttclient\src\swoole\message;


use mqttclient\src\consts\MessageType;

class Pingresp extends BaseMessage
{

    protected $type = MessageType::PINGRESP;

}