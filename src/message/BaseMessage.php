<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/3
 * Time: 上午10:34
 */

namespace mqttclient\src\message;

/**
 * 发消息封装
 * Class BaseMessage
 * @package mqttclient\src\message
 */
class BaseMessage implements MessageInterface
{

    protected $bytes;

    public function getBytes($id = null)
    {
        return $this->bytes;
    }
}