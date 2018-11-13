<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2018/11/13
 * Time: 下午3:10
 */

namespace mqttclient\src\mosquitto;


interface MqttSendingQueue
{

    /**
     * @return array|null
     */
    public function pop();

    /**
     * @param array $message ['topic' => need,'payload' => need,'qos' => default 0,'retain' => default false,'resend' => default false]
     * @return bool
     */
    public function push($message);
}