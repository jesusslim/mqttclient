<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/2
 * Time: 下午7:33
 */

namespace mqttclient\src\consts;


class MessageType
{

    const CONNECT = 1;
    const CONNACK = 2;
    const PUBLISH = 3;
    const PUBACK = 4;
    const PUBREC = 5;
    const PUBREL = 6;
    const PUBCOMP = 7;
    const SUBSCRIBE = 8;
    const SUBACK = 9;
    const UNSUBSCRIBE = 10;
    const UNSUBACK = 11;
    const PINGREQ = 12;
    const PINGRESP = 13;
    const DISCONNECT = 14;

}