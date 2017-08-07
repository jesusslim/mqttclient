<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/2
 * Time: 下午4:11
 */

namespace mqttclient\src\subscribe;

/**
 * Class Topic
 * @package mqttclient\src\subscribe
 */
class Topic
{

    protected $qos;
    protected $topic;
    protected $handler;

    public function __construct($topic,$handler,$qos = 0)
    {
        $this->topic = $topic;
        $this->handler = $handler;
        $this->qos = $qos;
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
     * @return \Closure
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * @param \Closure $handler
     */
    public function setHandler($handler)
    {
        $this->handler = $handler;
    }
}