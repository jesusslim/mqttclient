<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/3
 * Time: 上午11:17
 */

namespace mqttclient\src\receivemsg;

/**
 * 接收Publish消息处理
 * Class ReceivePublish
 * @package mqttclient\src\receivemsg
 */
class ReceivePublish
{

    private $topic_name;
    private $content;

    public function __construct($bytes_without_head)
    {
        $len = (ord($bytes_without_head{0})<<8) + ord($bytes_without_head{1});
        $this->topic_name = substr($bytes_without_head,2,$len);
        $this->content = substr($bytes_without_head,($len+2));
    }

    public function getTopicName(){
        return $this->topic_name;
    }

    public function getContent(){
        return $this->content;
    }
}