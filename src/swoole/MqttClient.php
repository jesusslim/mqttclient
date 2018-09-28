<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/3
 * Time: 下午8:33
 */

namespace mqttclient\src\swoole;


use Inject\Injector;
use mqttclient\src\consts\ClientTriggers;
use mqttclient\src\consts\MessageType;
use mqttclient\src\consts\MqttVersion;
use mqttclient\src\consts\Qos;
use mqttclient\src\exception\MqttClientException;
use mqttclient\src\swoole\message\MessageInterface;
use mqttclient\src\swoole\message\Publish;
use mqttclient\src\swoole\message\Suback;
use mqttclient\src\swoole\message\Unsubscribe;
use mqttclient\src\swoole\message\Will;

class MqttClient
{

    const TIMER_TAG = 'Timer_';

    /* @var \swoole_client $socket */
    private $socket;

    private $keep_alive;
    private $host;
    private $port;
    private $client_id;

    //协议最大长度
    private $package_max_length;

    //auth 加密 [user_name => '',password => '']
    private $auth;

    //遗嘱
    private $will;

    //subscribe订阅的topic
    private $topics;

    /**
     * @var MessageIdIterator
     */
    private $msg_id;

    private $mqtt_version;

    private $clean;

    /**
     * @var MqttLogInterface $logger
     */
    private $logger;

    /**
     * @var TmpStorageInterface $store
     */
    private $store;

    /*
     * 重发间隔 毫秒
     */
    private $resend_interval = 2000;

    private $resend_times = 1;

    private $max_reconnect_times_when_error = 2;

    private $reconnect_interval = 2000;

    private $reconnect_count = 0;

    /**** tmp ****/

    protected $keep_alive_timer_id;

    protected $connected_time = 0;
    protected $last_ping_time = 0;

    /**** tmp ****/

    /**** call back ****/

    private $call_backs = [];

    /**** call back ****/

    public function __construct($host,$port,$client_id)
    {
        $this->host = $host;
        $this->port = $port;
        $this->client_id = $client_id;
        $this->msg_id = new MessageIdIterator();
        $this->topics = [];
        $this->auth = null;
        $this->keep_alive = 60;
        $this->mqtt_version = MqttVersion::V311;
        $this->clean = true;
        $this->package_max_length = 1024*1024*2;
    }

    /**
     * @return mixed
     */
    public function getClientId()
    {
        return $this->client_id;
    }

    /**
     * @param mixed $client_id
     */
    public function setClientId($client_id)
    {
        $this->client_id = $client_id;
    }

    /**
     * @return int
     */
    public function getPackageMaxLength()
    {
        return $this->package_max_length;
    }

    /**
     * @param int $package_max_length
     */
    public function setPackageMaxLength($package_max_length)
    {
        $this->package_max_length = $package_max_length;
    }

    /**
     * @return Will
     */
    public function getWill()
    {
        return $this->will;
    }

    /**
     * @param Will $will
     */
    public function setWill($will)
    {
        $this->will = $will;
    }

    /**
     * @return int
     */
    public function getKeepAlive()
    {
        return $this->keep_alive;
    }

    /**
     * @param int $keep_alive
     */
    public function setKeepAlive($keep_alive)
    {
        $this->keep_alive = $keep_alive;
    }

    /**
     * @return array
     */
    public function getAuth()
    {
        return $this->auth;
    }

    /**
     * @param $user_name
     * @param $password
     */
    public function setAuth($user_name,$password)
    {
        $this->auth = compact('user_name','password');
    }

    /**
     * @return bool
     */
    public function getClean()
    {
        return $this->clean;
    }

    /**
     * @return int
     */
    public function getMqttVersion()
    {
        return $this->mqtt_version;
    }

    /**
     * @return MqttLogInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param MqttLogInterface $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return TmpStorageInterface
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * @param TmpStorageInterface $store
     */
    public function setStore($store)
    {
        $this->store = $store;
    }

    /**
     * @return int
     */
    public function getResendTimes()
    {
        return $this->resend_times;
    }

    /**
     * @param int $resend_times
     */
    public function setResendTimes($resend_times)
    {
        $this->resend_times = $resend_times;
    }

    /**
     * @return int
     */
    public function getResendInterval()
    {
        return $this->resend_interval;
    }

    /**
     * @param int $resend_interval
     */
    public function setResendInterval($resend_interval)
    {
        $this->resend_interval = $resend_interval;
    }

    /**
     * @param int $mqtt_version
     */
    public function setMqttVersion($mqtt_version)
    {
        $this->mqtt_version = $mqtt_version;
    }

    /**
     * @return array
     */
    public function getTopics()
    {
        return $this->topics;
    }

    /**
     * @param array $topics
     * @return $this
     */
    public function setTopics($topics)
    {
        foreach ($topics as $topic){
            /* @var \mqttclient\src\subscribe\Topic $topic */
            $this->topics[$topic->getTopic()] = $topic;
        }
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxReconnectTimesWhenError()
    {
        return $this->max_reconnect_times_when_error;
    }

    /**
     * @param int $max_reconnect_times_when_error
     */
    public function setMaxReconnectTimesWhenError($max_reconnect_times_when_error)
    {
        $this->max_reconnect_times_when_error = $max_reconnect_times_when_error;
    }

    /**
     * @return int
     */
    public function getReconnectInterval()
    {
        return $this->reconnect_interval;
    }

    /**
     * @param int $reconnect_interval
     */
    public function setReconnectInterval($reconnect_interval)
    {
        $this->reconnect_interval = $reconnect_interval;
    }

    /**
     * connect
     * @param bool $clean
     */
    public function connect($clean = true){
        if ($this->socket && $this->socket->isConnected()) return;
        $this->clean = $clean;
        $this->socket = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
        $this->socket->set([
            'open_mqtt_protocol'     => true,
            'package_max_length'    => $this->package_max_length,  //协议最大长度
        ]);
        $port = $this->port;

        $this->socket->on('connect',function ($cli){
            $this->reconnect_count = 0;
            /* @var \mqttclient\src\swoole\message\Connect */
            $msg = Message::produce(MessageType::CONNECT,$this);
            $this->write($msg);
            $this->logger->log(MqttLogInterface::DEBUG,'Connect');
            $this->keep_alive_timer_id = swoole_timer_tick($this->keep_alive * 500, [$this, 'keepAlive']);
            $this->trigger(ClientTriggers::SOCKET_CONNECT,null);
        });

        $this->socket->on('receive',function($cli,$data){
            /* @var MessageInterface $message */
            $message = $this->read($data);
            switch ($message->getType()){
                case MessageType::CONNACK:
                    $this->logger->log(MqttLogInterface::DEBUG,'Receive CONNACK.');
                    $this->subscribe();
                    $this->trigger(ClientTriggers::RECEIVE_CONNACK,$message);
                    break;
                case MessageType::PINGRESP:
                    $this->logger->log(MqttLogInterface::DEBUG,'Receive PINGRESP.');
                    $this->trigger(ClientTriggers::RECEIVE_PINGRESP,$message);
                    break;
                case MessageType::SUBACK:
                    $msg_id = $message->getMessageId();
                    $this->logger->log(MqttLogInterface::DEBUG,'Receive SUBACK.');
                    /* @var Suback $message */
                    $result = $message->getResult();
                    $req = $this->store->get(MessageType::SUBACK,'requesting',$msg_id);
                    if (!$req) {
                        $this->logger->log(MqttLogInterface::ERROR,'Subscribe request of msg id '.$msg_id.' not found.');
                    } else {
                        foreach ($result as $k => $qos) {
                            $topic_name = $req[$k];
                            if ($topic_name){
                                if ($qos != 0x80) {
                                    if (isset($this->topics[$topic_name])){
                                        $this->topics[$topic_name]->setQos($qos);
                                    }
                                } else {
                                    $this->logger->log(MqttLogInterface::ERROR,'Subscribe '.$topic_name.' fail.');
                                }
                            }else{
                                $this->logger->log(MqttLogInterface::ERROR,'Subscribe topic not found.');
                            }
                        }
                        $this->store->delete(MessageType::SUBACK,'requesting',$msg_id);
                    }
                    $this->trigger(ClientTriggers::RECEIVE_SUBACK,$message);
                    break;
                case MessageType::PUBLISH:
                    /* @var Publish $message */
                    $qos = $message->getQos();
                    $this->logger->log(MqttLogInterface::DEBUG,'Receive PUBLISH. QOS:'.$qos);
                    if ($qos == Qos::MOST_ONE_TIME){
                        $this->handleReceive($message);
                    }elseif ($qos == Qos::LEAST_ONE_TIME){
                        $this->handleReceive($message);
                        $msg_id = $message->getMessageId();
                        $this->logger->log(MqttLogInterface::DEBUG,'Puback');
                        $puback = Message::produce(MessageType::PUBACK,$this);
                        $puback->setMessageId($msg_id);
                        $this->write($puback);
                    }elseif ($qos == Qos::ONE_TIME){
                        $msg_id = $message->getMessageId();
                        $this->logger->log(MqttLogInterface::DEBUG,'Pubrec');
                        $pubrec = Message::produce(MessageType::PUBREC,$this);
                        $pubrec->setMessageId($msg_id);
                        $this->write($pubrec);
                        $this->getStore()->set(MessageType::PUBREL,'pubrecing',$msg_id,$pubrec->encode());
                        $this->registerResend(MessageType::PUBREL,'pubrecing',$msg_id,$this->resend_times);
                        $this->handleReceive($message);
                    }
                    $this->trigger(ClientTriggers::RECEIVE_PUBLISH,$message);
                    break;
                case MessageType::PUBACK:
                    $msg_id = $message->getMessageId();
                    $this->logger->log(MqttLogInterface::DEBUG,'Receive PUBACK. MESSAGE_ID:'.$msg_id);
                    $this->getStore()->delete(MessageType::PUBACK,'publishing',$msg_id);
                    $this->unregisterResend(MessageType::PUBACK,'publishing',$msg_id);
                    $this->trigger(ClientTriggers::RECEIVE_PUBACK,$message);
                    break;
                case MessageType::PUBREC:
                    $msg_id = $message->getMessageId();
                    $this->logger->log(MqttLogInterface::DEBUG,'Receive PUBREC. MESSAGE_ID:'.$msg_id);
                    $this->getStore()->delete(MessageType::PUBREC,'publishing',$msg_id);
                    $this->unregisterResend(MessageType::PUBREC,'publishing',$msg_id);
                    $pubrel = Message::produce(MessageType::PUBREL,$this);
                    $pubrel->setMessageId($msg_id);
                    $this->write($pubrel);
                    $this->getStore()->set(MessageType::PUBCOMP,'pubreling',$msg_id,$pubrel->encode());
                    $this->registerResend(MessageType::PUBCOMP,'pubreling',$msg_id,$this->resend_times);
                    $this->trigger(ClientTriggers::RECEIVE_PUBREC,$message);
                    break;
                case MessageType::PUBREL:
                    $msg_id = $message->getMessageId();
                    $this->logger->log(MqttLogInterface::DEBUG,'Receive PUBREL. MESSAGE_ID:'.$msg_id);
                    $this->getStore()->delete(MessageType::PUBREL,'pubrecing',$msg_id);
                    $this->unregisterResend(MessageType::PUBREL,'pubrecing',$msg_id);
                    $pubcomp = Message::produce(MessageType::PUBCOMP,$this);
                    $pubcomp->setMessageId($msg_id);
                    $this->write($pubcomp);
                    $this->trigger(ClientTriggers::RECEIVE_PUBREL,$message);
                    break;
                case MessageType::PUBCOMP:
                    $msg_id = $message->getMessageId();
                    $this->logger->log(MqttLogInterface::DEBUG,'Receive PUBCOMP. MESSAGE_ID:'.$msg_id);
                    $this->getStore()->delete(MessageType::PUBCOMP,'pubreling',$msg_id);
                    $this->unregisterResend(MessageType::PUBCOMP,'pubreling',$msg_id);
                    $this->trigger(ClientTriggers::RECEIVE_PUBCOMP,$message);
                    break;
                case MessageType::UNSUBACK:
                    $msg_id = $message->getMessageId();
                    $this->logger->log(MqttLogInterface::DEBUG,'Receive UNSUBACK.');
                    $topics = $this->store->get(MessageType::UNSUBACK,'requesting',$msg_id);
                    if (!$topics) {
                        $this->logger->log(MqttLogInterface::ERROR,'Unsubscribe request of msg id '.$msg_id.' not found.');
                    } else {
                        foreach ($topics as $topic) {
                            $this->logger->log(MqttLogInterface::DEBUG,'Unsubscribe '.$topic);
                            unset($this->topics[$topic]);
                        }
                        $this->store->delete(MessageType::UNSUBACK,'requesting',$msg_id);
                    }
                    $this->trigger(ClientTriggers::RECEIVE_UNSUBACK,$message);
                    break;
                default:
                    break;
            }
            $this->trigger(ClientTriggers::SOCKET_RECEIVE,$message);
        });

        $this->socket->on('error',function($error){
            $this->logger->log(MqttLogInterface::ERROR,'Connect fail:'.json_encode($error));
            $this->reconnect_count ++;
            if ($this->max_reconnect_times_when_error == 0 || $this->reconnect_count <= $this->max_reconnect_times_when_error){
                swoole_timer_after($this->reconnect_interval,[$this,'connect']);
            }else{
                $this->disconnect();
            }
            $this->trigger(ClientTriggers::SOCKET_ERROR,null);
        });

        $this->socket->on('close',function(){
            $this->logger->log(MqttLogInterface::ERROR,'Connect close.');
            if ($this->keep_alive_timer_id != null) {
                swoole_timer_clear($this->keep_alive_timer_id);
                $this->keep_alive_timer_id = null;
            }

            $this->reconnect_count ++;
            if ($this->max_reconnect_times_when_error == 0 || $this->reconnect_count <= $this->max_reconnect_times_when_error){
                swoole_timer_after($this->reconnect_interval,[$this,'connect']);
            }else{
                $this->disconnect();
            }

            $this->trigger(ClientTriggers::SOCKET_CLOSE,null);
        });

        swoole_async_dns_lookup($this->host, function($host, $ip) use($port){
            $this->socket->connect($ip, $port);
        });
    }

    /**
     * write
     * @param MessageInterface $msg
     * @return bool
     * @throws MqttClientException
     */
    protected function write(MessageInterface $msg){
        if (is_null($this->socket)) throw new MqttClientException('socket is null');
        return $this->socket->send($msg->encode());
    }

    /**
     * read
     * @param $data
     * @return bool|MessageInterface
     * @throws MqttClientException
     */
    protected function read($data){
        $type = Util::decodeCmd(ord($data{0}));
        $index = 1;
        $remaining_length = Util::decodeRemainLength($data,$index);
        $msg = Message::produce($type,$this);
        if ($msg === false) throw new MqttClientException("read message produce fail $type");
        $msg->decode($data,$remaining_length);
        return $msg;
    }

    /**
     * keep alive
     */
    public function keepAlive(){
        $current_time = time();
        if (empty($this->last_ping_time)) {
            if ($this->connected_time) {
                $this->last_ping_time = $this->connected_time;
            } else {
                $this->last_ping_time = $current_time;
            }
        }
        $this->ping();
    }

    /**
     * ping
     * @return bool
     */
    public function ping(){
        $this->logger->log(MqttLogInterface::DEBUG,'Ping');
        $ping = Message::produce(MessageType::PINGREQ,$this);
        $r = $this->write($ping);
        $this->trigger(ClientTriggers::PING,$ping);
        return $r;
    }

    /**
     * disconnect
     * @return bool
     */
    public function disconnect()
    {
        $this->logger->log(MqttLogInterface::DEBUG,'Disconnect');
        $disconnect = Message::produce(MessageType::DISCONNECT,$this);
        if ($this->socket && $this->socket->isConnected()){
            $this->write($disconnect);
            $this->socket->close();
        }
        $this->trigger(ClientTriggers::DISCONNECT,$disconnect);
        return true;
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * reconnect
     * @param bool $must
     * @param bool $clean_session
     */
    public function reconnect($must = true,$clean_session = false)
    {
        $this->logger->log(MqttLogInterface::DEBUG,'Reconnect');
        if ($must) {
            $this->disconnect();
        }
        $this->connect($clean_session);
    }

    /**
     * subscribe
     * @return int
     */
    public function subscribe()
    {
        $this->logger->log(MqttLogInterface::DEBUG,'Subscribe');
        if (count($this->topics) == 0) return false;
        $id = $this->msg_id->next();
        $subscribe = Message::produce(MessageType::SUBSCRIBE,$this);
        $subscribe->setMessageId($id);
        $this->write($subscribe);
        $this->store->set(MessageType::SUBACK,'requesting',$id,array_keys($this->getTopics()));
        $this->trigger(ClientTriggers::SUBSCRIBE,$subscribe);
        return $id;
    }

    /**
     * unsubscribe
     * @param array $topics
     * @return int
     */
    public function unsubscribe($topics){
        $this->logger->log(MqttLogInterface::DEBUG,'Unsubscribe');
        $id = $this->msg_id->next();
        $unsubscribe = Message::produce(MessageType::UNSUBSCRIBE,$this);
        /* @var Unsubscribe $unsubscribe */
        $unsubscribe->setMessageId($id);
        $unsubscribe->setTopics($topics);
        $this->write($unsubscribe);
        $this->store->set(MessageType::UNSUBACK,'requesting',$id,array_values($topics));
        $this->trigger(ClientTriggers::UNSUBSCRIBE,$unsubscribe);
        return $id;
    }

    /**
     * @param $topic
     * @param $message
     * @param int $qos
     * @param int $retain
     * @param int $dup
     * @param int $msg_id
     * @return int
     */
    public function publish($topic,$message,$qos = Qos::MOST_ONE_TIME,$retain = 0,$dup = 0,$msg_id = 0){
        /* @var Publish $publish */
        $publish = Message::produce(MessageType::PUBLISH,$this);
        $publish->setTopic($topic);
        $publish->setMessage($message);
        $publish->setQos($qos);
        $publish->setRetain($retain);
        $publish->setDup($dup);
        if ($qos > 0 && !($msg_id > 0)){
            $msg_id = $this->msg_id->next();
        }
        $publish->setMessageId($msg_id);
        $this->write($publish);
        $this->logger->log(MqttLogInterface::DEBUG,'Publish '.$msg_id);
        if ($qos == Qos::LEAST_ONE_TIME){
            //Publish Puback
            //dup 1 表示可能是早前报文的重发
            if (!$dup) {
                $this->getStore()->set(MessageType::PUBACK,'publishing',$msg_id,$publish->encode());
                $this->registerResend(MessageType::PUBACK,'publishing',$msg_id,$this->resend_times);
            }
        }elseif($qos == Qos::ONE_TIME){
            //Publish Pubrec Pubrel Pubcomp
            if (!$dup){
                $this->getStore()->set(MessageType::PUBREC,'publishing',$msg_id,$publish->encode());
                $this->registerResend(MessageType::PUBREC,'publishing',$msg_id,$this->resend_times);
            }
        }
        $this->trigger(ClientTriggers::PUBLISH,$publish);
        return $msg_id;
    }

    /**
     * 重发设置
     * @param int $store_msg_type msg type
     * @param string $store_key action
     * @param int|string $store_sub_key msgid
     * @param int $times
     */
    public function registerResend($store_msg_type,$store_key,$store_sub_key,$times){
        if ($times <= 0) return;
        $timer_id = swoole_timer_after($this->resend_interval,function () use ($store_msg_type,$store_key,$store_sub_key,$times){
            $this->logger->log(MqttLogInterface::DEBUG,'Try Resend. MESSAGE TYPE:'.$store_msg_type.' ACTION:'.$store_key.' MESSAGE_ID:'.$store_sub_key .' REMAIN TIMES:'.$times);
            $stored = $this->getStore()->get($store_msg_type,$store_key,$store_sub_key);
            if ($stored){
                $this->socket->send($stored);
                $this->logger->log(MqttLogInterface::DEBUG,'Already Resend. MESSAGE TYPE:'.$store_msg_type.' ACTION:'.$store_key.' MESSAGE_ID:'.$store_sub_key);
                $this->registerResend($store_msg_type,$store_key,$store_sub_key,$times-1);
            }
        });
        $this->getStore()->set(self::TIMER_TAG.$store_msg_type,$store_key,$store_sub_key,$timer_id);
    }

    /**
     * 取消重发
     * @param int $store_msg_type msg type
     * @param string $store_key action
     * @param int|string $store_sub_key msgid
     */
    public function unregisterResend($store_msg_type,$store_key,$store_sub_key){
        $timer_id = $this->getStore()->get(self::TIMER_TAG.$store_msg_type,$store_key,$store_sub_key);
        if ($timer_id > 0) swoole_timer_clear($timer_id);
        $this->getStore()->delete(self::TIMER_TAG.$store_msg_type,$store_key,$store_sub_key);
    }

    /**
     * 处理接收到的Publish
     * @param $str
     */
    public function handleReceive(Publish $publish){
        $topic_name = $publish->getTopic();
        $container = $this->produceContainer();
        foreach($this->topics as $key => $topic){
            if( preg_match("/^".str_replace("#",".*",
                    str_replace("+","[^\/]*",
                        str_replace("/","\/",
                            str_replace("$",'\$',
                                $key))))."$/",$topic_name) ){
                /* @var \mqttclient\src\subscribe\Topic $topic */
                $container->call($topic->getHandler(),['msg' => $publish->getMessage(),'msg_id' => $publish->getMessageId(),'topic' => $publish->getTopic()]);
            }
        }
    }

    /**
     * 容器
     * @return Injector
     */
    protected function produceContainer(){
        $container = new Injector();
        $container->mapData(MqttClient::class,$this);
        return $container;
    }

    /**
     * check memory
     */
    public function checkMemory(){
        $use_age = memory_get_usage();
        $this->logger->log(MqttLogInterface::INFO,'MEMORY USAGE:'.$use_age);
    }

    /**
     * set a trigger
     * @param $trigger
     * @param \Closure $call_back
     */
    public function on($trigger,\Closure $call_back){
        $this->call_backs[$trigger] = $call_back;
        return $this;
    }

    /**
     * trigger
     * @param $trigger
     * @param MessageInterface|null $msg
     */
    protected function trigger($trigger,MessageInterface $msg = null){
        if (isset($this->call_backs[$trigger])){
            $container = $this->produceContainer();
            $container->call($this->call_backs[$trigger],['msg' => $msg]);
        }
    }

    /**
     * @return \swoole_client
     */
    public function getSocket(){
        return $this->socket;
    }
}