<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/9
 * Time: 下午4:28
 */

namespace mqttclient\src\consts;


class ClientTriggers
{

    const SOCKET_CONNECT = 'socket_connect';
    const SOCKET_RECEIVE = 'socket_receive';
    const SOCKET_ERROR = 'socket_error';
    const SOCKET_CLOSE = 'socket_close';

    const RECEIVE_CONNACK = 'receive_connack';
    const RECEIVE_PINGRESP = 'receive_pingresp';
    const RECEIVE_SUBACK = 'receive_suback';
    const RECEIVE_PUBLISH = 'receive_publish';
    const RECEIVE_PUBACK = 'receive_puback';
    const RECEIVE_PUBREC = 'receive_pubrec';
    const RECEIVE_PUBREL = 'receive_pubrel';
    const RECEIVE_PUBCOMP = 'receive_pubcomp';
    const RECEIVE_UNSUBACK = 'receive_unsuback';
    const PING = 'ping';
    const DISCONNECT = 'disconnect';
    const SUBSCRIBE = 'subscribe';
    const UNSUBSCRIBE = 'unsubscribe';
    const PUBLISH = 'publish';
}