<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/3
 * Time: 上午10:31
 */

namespace mqttclient\src\message;

/**
 * Class Ping
 * @package mqttclient\src\message
 */
class Ping extends BaseMessage
{

    public function __construct()
    {
        //11000000
        //00000000
        $head = chr(0xc0);
        $head .= chr(0x00);
        $this->bytes = $head;
    }
}