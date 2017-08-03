<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/3
 * Time: 上午11:04
 */

namespace mqttclient\src\message;

/**
 * Class Subscribe
 * @package mqttclient\src\message
 */
class Subscribe extends BaseMessage
{

    public function __construct($topics,$msg_id,$qos)
    {
        $buffer = "";
        $buffer .= chr($msg_id >> 8);
        $buffer .= chr($msg_id % 256);
        foreach($topics as $key => $topic){
            /* @var \mqttclient\src\subscribe\Topic $topic */
            $buffer .= Util::writeToBuffer($key);
            $buffer .= chr($topic->getQos());
        }
        $cmd = 0x80;
        $cmd +=	($qos << 1);
        $head = chr($cmd);
        $head .= chr(strlen($buffer));
        $this->bytes = $head.$buffer;
    }

}