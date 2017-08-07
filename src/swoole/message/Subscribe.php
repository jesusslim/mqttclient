<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/3
 * Time: 下午8:26
 */

namespace mqttclient\src\swoole\message;


use mqttclient\src\consts\MessageType;
use mqttclient\src\swoole\Util;

class Subscribe extends BaseMessage
{

    protected $type = MessageType::SUBSCRIBE;

    protected $need_message_id = true;

    public function getPayload()
    {
        $buffer = "";
        $topics = $this->getClient()->getTopics();
        /* @var \mqttclient\src\subscribe\Topic $topic */
        foreach ($topics as $topic_name => $topic){
            $buffer .= Util::packLenStr($topic->getTopic());
            $buffer .= chr($topic->getQos());
        }
        return $buffer;
    }

}