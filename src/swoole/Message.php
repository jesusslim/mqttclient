<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/3
 * Time: 下午8:23
 */

namespace mqttclient\src\swoole;


use mqttclient\src\consts\MessageType;
use mqttclient\src\swoole\message\Connack;
use mqttclient\src\swoole\message\Connect;
use mqttclient\src\swoole\message\Disconnect;
use mqttclient\src\swoole\message\MessageInterface;
use mqttclient\src\swoole\message\Pingreq;
use mqttclient\src\swoole\message\Pingresp;
use mqttclient\src\swoole\message\Puback;
use mqttclient\src\swoole\message\Pubcomp;
use mqttclient\src\swoole\message\Publish;
use mqttclient\src\swoole\message\Pubrec;
use mqttclient\src\swoole\message\Pubrel;
use mqttclient\src\swoole\message\Suback;
use mqttclient\src\swoole\message\Subscribe;
use mqttclient\src\swoole\message\Unsuback;
use mqttclient\src\swoole\message\Unsubscribe;

class Message
{

    const CLASS_MAP = [
        MessageType::CONNECT => Connect::class,
        MessageType::CONNACK => Connack::class,
        MessageType::PUBLISH => Publish::class,
        MessageType::PUBACK => Puback::class,
        MessageType::PUBREC => Pubrec::class,
        MessageType::PUBREL => Pubrel::class,
        MessageType::PUBCOMP => Pubcomp::class,
        MessageType::SUBSCRIBE => Subscribe::class,
        MessageType::SUBACK => Suback::class,
        MessageType::UNSUBSCRIBE => Unsubscribe::class,
        MessageType::UNSUBACK => Unsuback::class,
        MessageType::PINGREQ => Pingreq::class,
        MessageType::PINGRESP => Pingresp::class,
        MessageType::DISCONNECT => Disconnect::class
    ];

    /**
     * @param $type
     * @param $client
     * @return MessageInterface|bool
     */
    public static function produce($type,$client){
        $cls = self::CLASS_MAP[$type];
        if ($cls){
            return new $cls($client);
        }
        return false;
    }

}