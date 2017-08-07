<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/4
 * Time: 上午12:12
 */

namespace mqttclient\src\swoole\message;


use mqttclient\src\swoole\MqttClient;

interface MessageInterface
{

    public function getType();

    /**
     * @return MqttClient
     */
    public function getClient();

    public function getMessageId();

    public function setMessageId($message_id);

    /**
     * @return string
     */
    public function encode();

    public function decode($data,$remain_length);

    /**
     * @return string
     */
    public function getPayload();

    public function setPayload($payload);

    public function decodePayload($data,$pos);


    /* header */

    public function getRemainLength();

    public function setRemainLength($remain_length);

    public function setPayloadLength($payload_length);

    public function getFullLength();

    public function encodeVariableHeader();

    public function encodeHeader();

    public function decodeVariableHeader($data,&$pos);

    public function decodeHeader($data,$remain_length);
}