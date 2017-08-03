<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/2
 * Time: 下午1:52
 */

namespace mqttclient\src\message;

/**
 * 基础Publish消息
 * Class BasePublish
 * @package mqttclient\src\message
 */
class BasePublish implements PublishInterface,MessageInterface
{

    protected $qos;
    protected $topic;
    protected $content;
    protected $retain;
    private $bytes;

    public function __construct($topic,$content,$qos = 0,$retain = 0)
    {
        $this->topic = $topic;
        $this->content = $content;
        $this->qos = $qos;
        $this->retain = $retain;
    }

    /**
     * @return int
     */
    public function getRetain()
    {
        return $this->retain;
    }

    /**
     * @param int $retain
     */
    public function setRetain($retain)
    {
        $this->retain = $retain;
    }

    /**
     * @return int
     */
    public function getQos()
    {
        return $this->qos;
    }

    /**
     * @param int $qos
     */
    public function setQos($qos)
    {
        $this->qos = $qos;
    }

    /**
     * @return string
     */
    public function getTopic()
    {
        return $this->topic;
    }

    /**
     * @param string $topic
     */
    public function setTopic($topic)
    {
        $this->topic = $topic;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @param null $id
     */
    public function getBytes($id = null)
    {
        $buffer = "";
        $buffer .= Util::writeToBuffer($this->getTopic());
        if($this->getQos()){
            $buffer .= chr($id >> 8);
            $buffer .= chr($id % 256);
        }
        $buffer .= $this->getContent();
        $cmd = 0x30;
        if($this->getQos()) $cmd += $this->getQos() << 1;
        if($this->getRetain()) $cmd += 1;
        $head = chr($cmd);
        $head .= Util::writeMsgLength(strlen($buffer));
        $this->bytes = $head.$buffer;
        return $this->bytes;
    }
}