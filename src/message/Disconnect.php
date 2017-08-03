<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/3
 * Time: 上午10:38
 */

namespace mqttclient\src\message;

/**
 * Class Disconnect
 * @package mqttclient\src\message
 */
class Disconnect extends BaseMessage
{

    public function __construct()
    {
        $head = chr(0xe0);
        $head .= chr(0x00);
        $this->bytes = $head;
    }

}