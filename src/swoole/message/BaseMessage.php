<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/3
 * Time: 下午10:02
 */

namespace mqttclient\src\swoole\message;


use mqttclient\src\swoole\MqttClient;
use mqttclient\src\swoole\Util;

class BaseMessage implements MessageInterface
{

    protected $type;

    /* @var MqttClient */
    private $client;

    private $payload;

    private $message_id;


    /* header */

    private $remain_length = 0;

    protected $need_message_id = false;

    protected $reserved_flags = 0;

    public function __construct(MqttClient $client)
    {
        $this->client = $client;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return MqttClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return string
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @param string $payload
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;
    }

    /**
     * @return string
     */
    public function getMessageId()
    {
        return $this->message_id;
    }

    /**
     * @param string $message_id
     */
    public function setMessageId($message_id)
    {
        $this->message_id = $message_id;
    }

    /**
     * @return string
     */
    public function encode()
    {
        $payload = $this->getPayload();
        $this->setPayloadLength(strlen($payload));
        return $this->encodeHeader().$payload;
    }

    public function decode($data,$remain_length){
        $pos_now = $this->decodeHeader($data, $remain_length);
        return $this->decodePayload($data, $pos_now);
    }

    public function decodePayload($data,$pos){
        return true;
    }

    /**
     * @return mixed
     */
    public function getRemainLength()
    {
        return $this->remain_length;
    }

    /**
     * @param mixed $remain_length
     */
    public function setRemainLength($remain_length)
    {
        $this->remain_length = $remain_length;
    }

    /**
     * @return mixed
     */
    public function getReservedFlags()
    {
        return $this->reserved_flags;
    }

    /**
     * @param mixed $reserved_flags
     */
    public function setReservedFlags($reserved_flags)
    {
        $this->reserved_flags = $reserved_flags;
    }

    /**
     * @param int $payload_length
     */
    public function setPayloadLength($payload_length)
    {
        $this->setRemainLength($payload_length + strlen($this->encodeVariableHeader()));
    }

    /**
     * @return int
     */
    public function getFullLength()
    {
        $cmd_length = 1;
        return $cmd_length + strlen(Util::packRemainLength($this->remain_length)) + $this->remain_length;
    }

    public function encodeHeader(){
        $cmd = $this->getType() << 4;
        $cmd |= ($this->reserved_flags & 0x0F);
        $header = chr($cmd) . Util::packRemainLength($this->remain_length);
        $header .= $this->encodeVariableHeader();
        return $header;
    }

    public function decodeHeader($data,$remain_length){
        $flags = Util::decodeFlags(ord($data{0}));
        $this->setFlags($flags);
        $remain_length_length = strlen(Util::packRemainLength($remain_length));
        $pos = 1 + $remain_length_length;
        $this->remain_length = $remain_length;
        $this->decodeVariableHeader($data,$pos);
        return $pos;
    }

    protected function setFlags($flags){
        return true;
    }

    public function encodeVariableHeader()
    {
        $buffer = '';
        if ($this->need_message_id) {
            $buffer .= pack('n', $this->getMessageId());
        }
        return $buffer;
    }

    public function decodeVariableHeader($data,&$pos)
    {
        return true;
    }
}