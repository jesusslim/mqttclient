<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/3
 * Time: 下午8:27
 */

namespace mqttclient\src\swoole\message;


use mqttclient\src\consts\MessageType;
use mqttclient\src\swoole\Util;

class Unsubscribe extends BaseMessage
{

    protected $type = MessageType::UNSUBSCRIBE;

    protected $topics = [];

    protected $reserved_flags = 0x02;

    protected $need_message_id = true;

    /**
     * @return array
     */
    public function getTopics()
    {
        return $this->topics;
    }

    /**
     * @param array $topics
     */
    public function setTopics($topics)
    {
        $this->topics = $topics;
    }

    public function getPayload()
    {
        $buffer = "";
        foreach ($this->topics as $topic_name){
            $buffer .= Util::packLenStr($topic_name);
        }
        return $buffer;
    }
}