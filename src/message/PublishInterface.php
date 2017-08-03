<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/2
 * Time: 下午2:16
 */

namespace mqttclient\src\message;


Interface PublishInterface
{

    public function getQos();

    public function setQos($qos);

    public function getTopic();

    public function setTopic($topic);

    public function getContent();

    public function setContent($content);

    public function getRetain();

    public function setRetain($retain);
}