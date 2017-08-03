<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/3
 * Time: 上午10:34
 */

namespace mqttclient\src\message;


interface MessageInterface
{

    public function getBytes($id = null);

}