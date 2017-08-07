<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/3
 * Time: 下午8:26
 */

namespace mqttclient\src\swoole\message;


use mqttclient\src\consts\MessageType;
use mqttclient\src\consts\Qos;
use mqttclient\src\swoole\Util;

class Publish extends BaseMessage
{

    protected $type = MessageType::PUBLISH;

    protected $topic;
    protected $message;
    protected $qos;
    protected $dup;
    protected $retain;

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
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
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
     * @return int
     */
    public function getDup()
    {
        return $this->dup;
    }

    /**
     * @param int $dup
     */
    public function setDup($dup)
    {
        $this->dup = $dup;
    }

    /**
     * @return string
     */
    public function getPayload()
    {
        return $this->message;
    }

    /**
     * @param $data
     * @param $pos
     */
    public function decodePayload($data, $pos)
    {
        $this->message = substr($data,$pos);
    }

    public function encodeVariableHeader()
    {
        $buffer = '';

        $buffer .= Util::packLenStr($this->getTopic());

        if ($this->getQos() > Qos::MOST_ONE_TIME){
            $buffer .= pack('n', $this->getMessageId());
        }

        return $buffer;
    }

    public function setFlags($flags)
    {
        $dup = ($flags & 0x08) >> 3;
        $qos = ($flags & 0x06) >> 1;
        $retain = ($flags & 0x01);
        $this->setDup($dup);
        $this->setQos($qos);
        $this->setRetain($retain);
    }

    public function decodeVariableHeader($data, &$pos)
    {
        $topic = Util::decodeString($data,$pos);
        $this->setTopic($topic);
        if ($this->getQos() > Qos::MOST_ONE_TIME){
            $message_id = Util::decodeUnsignedShort($data,$pos);
            $this->setMessageId($message_id);
        }
        return true;
    }

    public function encodeHeader()
    {
        $flags = 0;
        $flags |= ($this->getDup() << 3);
        $flags |= ($this->getQos() << 1);
        $flags |= $this->getRetain();
        $this->reserved_flags = $flags;
        return parent::encodeHeader();
    }
}