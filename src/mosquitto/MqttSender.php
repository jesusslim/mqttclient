<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2018/11/13
 * Time: 下午3:00
 */

namespace mqttclient\src\mosquitto;


use mqttclient\src\consts\ClientTriggers;
use mqttclient\src\consts\Qos;
use mqttclient\src\swoole\MqttLogInterface;
use Exception;

class MqttSender extends MqttClient
{

    /**
     * @var MqttSendingQueue
     */
    private $queue;

    /**
     * 消息重发间隔秒数
     * @var int
     */
    private $message_retry_period;

    /**
     * @return int
     */
    public function getMessageRetryPeriod()
    {
        return $this->message_retry_period;
    }

    /**
     * @param int $message_retry_period
     */
    public function setMessageRetryPeriod($message_retry_period)
    {
        $this->message_retry_period = $message_retry_period;
    }

    /**
     * @return MqttSendingQueue
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * @param MqttSendingQueue $queue
     */
    public function setQueue($queue)
    {
        $this->queue = $queue;
    }

    /**
     * @param bool $clean
     */
    public function init($clean = true)
    {
        parent::init($clean);
        if ($this->message_retry_period > 0) $this->client->setMessageRetry($this->message_retry_period);
    }

    /**
     * @param bool $clean
     */
    public function start($clean = true)
    {
        $this->init($clean);
        $this->connect();
    }

    /**
     *
     */
    public function connect()
    {
        $this->getLogger()->log(MqttLogInterface::INFO,'Sender Connect');
        try{
            $this->client->connect($this->host,$this->port,$this->keep_alive);
        }catch (Exception $exception){
            $this->getLogger()->log(MqttLogInterface::ERROR,'Connect error:'.$exception->getMessage());
            return $this->reconnect();
        }
        while (true){
            try{
                $this->client->loop($this->getLoopTimeout());
            }catch (Exception $exception){
                $this->getLogger()->log(MqttLogInterface::ERROR,'Loop error:'.$exception->getMessage());
                return $this->reconnect();
            }
            $data = $this->queue->pop();
            if ($data){
                try{
                    $this->client->publish($data['topic'],is_array($data['payload']) ? json_encode($data['payload']) : $data['payload'],$data['qos'] ? : Qos::LEAST_ONE_TIME,$data['retain'] ? : false);
                    $this->trigger(ClientTriggers::PUBLISH,$data);
                }catch (Exception $exception){
                    $this->getLogger()->log(MqttLogInterface::ERROR,'Publish error:'.$exception->getMessage().' '.json_encode($data));
                    if ($data['resend'] === true){
                        $data['resend'] = false;
                        $this->queue->push($data);
                    }
                }
            }
        }
        $this->reconnect();
    }
}