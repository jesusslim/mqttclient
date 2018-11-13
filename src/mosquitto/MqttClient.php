<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2018/11/13
 * Time: 上午10:50
 */

namespace mqttclient\src\mosquitto;


use Inject\Injector;
use Mosquitto\Client;
use Mosquitto\Message;
use mqttclient\src\consts\ClientTriggers;
use mqttclient\src\subscribe\Topic;
use mqttclient\src\swoole\message\Will;
use mqttclient\src\swoole\MqttLogInterface;
use Exception;

class MqttClient
{

    /**
     * @var Client
     */
    protected $client;
    protected $keep_alive;
    protected $host;
    protected $port;
    private $client_id;

    //auth 加密 [user_name => '',password => '']
    private $auth;

    /**
     * 遗嘱
     * @var Will
     */
    private $will;

    //subscribe订阅的topic
    private $topics;

    private $clean;

    /**
     * @var MqttLogInterface $logger
     */
    private $logger;

    /**** call back ****/

    private $call_backs = [];

    /**** reconnect ****/
    private $loop_timeout = 1000;

    private $max_reconnect_times_when_error = 2;

    private $reconnect_interval = 2000;

    private $reconnect_count = 0;

    /**** storage for sub ****/

    private $subscribe_ids;

    public function __construct($host,$port,$client_id)
    {
        $this->host = $host;
        $this->port = $port;
        $this->client_id = $client_id;
        $this->topics = [];
        $this->auth = null;
        $this->clean = true;
        $this->keep_alive = 60;
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
     * @return int
     */
    public function getLoopTimeout()
    {
        return $this->loop_timeout;
    }

    /**
     * @param int $loop_timeout
     */
    public function setLoopTimeout($loop_timeout)
    {
        $this->loop_timeout = $loop_timeout;
    }

    /**
     * @param bool $clean
     */
    public function init($clean = true){
        $this->clean = $clean;
        $this->client = new Client($this->client_id,$clean);
        $auth = $this->auth;
        if ($auth){
            $this->client->setCredentials($auth['user_name'],$auth['password']);
        }
        if ($this->will){
            $this->client->setWill($this->will->getTopic(),$this->will->getPayload(),$this->will->getQos(),$this->will->getRetain());
        }
        //setReconnectDelay
        $this->client->onConnect(function($code,$message){
            if ($code == 0){
                $this->logger->log(MqttLogInterface::DEBUG,'Connect');
                $this->trigger(ClientTriggers::SOCKET_CONNECT,$message);
                $this->subscribe();
            }else{
                switch ($code){
                    case 1:
                        $err = 'Connection refused (unacceptable protocol version)';
                        break;
                    case 2:
                        $err = 'Connection refused (identifier rejected)';
                        break;
                    case 3:
                        $err = 'Connection refused (broker unavailable)';
                        break;
                    default:
                        $err = 'Unknown err '.$code;
                        break;
                }
                $this->logger->log(MqttLogInterface::ERROR,'Connect Error:'.$err);
            }
        });
        $this->client->onSubscribe(function($mid,$qos){
            $topic = $this->subscribe_ids[$mid];
            $this->logger->log(MqttLogInterface::INFO,'Subscribe '.$topic.' Qos '.$qos);
        });
        $this->client->onMessage(function($message){
            /* @var Message $message */
            $topic_name = $message->topic;
            $container = $this->produceContainer();
            foreach($this->topics as $key => $topic){
                if( preg_match("/^".str_replace("#",".*",
                        str_replace("+","[^\/]*",
                            str_replace("/","\/",
                                str_replace("$",'\$',
                                    $key))))."$/",$topic_name) ){
                    /* @var \mqttclient\src\subscribe\Topic $topic */
                    $container->call($topic->getHandler(),['msg' => $message->payload,'msg_id' => $message->mid,'topic' => $message->topic]);
                }
            }
            unset($container);
        });
        $this->client->onLog(function($level,$str){
            if (in_array($level,[Client::LOG_ERR,Client::LOG_WARNING])){
                $this->logger->log(MqttLogInterface::ERROR,'Error/Warning from mosquitto:'.$str);
            }
        });
        $this->client->onDisconnect(function($rc){
            $this->logger->log(MqttLogInterface::INFO,'Disconnected '.$rc);
        });
    }

    /**
     * @param bool $clean
     */
    public function start($clean = false){
        $this->init($clean);
        $this->connect();
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
     * subscribe
     * @return array
     */
    public function subscribe()
    {
        $result = [];
        $this->logger->log(MqttLogInterface::DEBUG,'Subscribe');
        if (count($this->topics) == 0) return $result;
        foreach ($this->topics as $topic_name => $topic){
            /* @var Topic $topic */
            $msg_id = $this->client->subscribe($topic_name,$topic->getQos());
            $result[$msg_id] = $topic_name;
        }
        $this->subscribe_ids = $result;
        $this->trigger(ClientTriggers::SUBSCRIBE);
        return $result;
    }

    /**
     * set a trigger
     * @param $trigger
     * @param \Closure $call_back
     * @param string $cb_id callback_id/key
     */
    public function on($trigger,\Closure $call_back,$cb_id = 'default'){
        $this->call_backs[$trigger][$cb_id] = $call_back;
        return $this;
    }

    /**
     * @param $trigger
     * @param $msg
     */
    protected function trigger($trigger,$msg = null){
        if (isset($this->call_backs[$trigger])){
            $container = $this->produceContainer();
            foreach ($this->call_backs[$trigger] as $cb_id => $call_back){
                $container->call($call_back,compact('msg','container'));
            }
        }
        unset($container);
    }

    /**
     *
     */
    public function reconnect(){
        $this->reconnect_count ++;
        if ($this->max_reconnect_times_when_error == 0 || $this->reconnect_count <= $this->max_reconnect_times_when_error){
            sleep($this->reconnect_interval);
            $this->connect();
        }else{
            $this->logger->log(MqttLogInterface::ERROR,'Reconnect max retry times');
        }
    }

    /**
     *
     */
    public function connect(){
        $this->logger->log(MqttLogInterface::INFO,'Subscriber Connect');
        try{
            $this->client->connect($this->host,$this->port,$this->keep_alive);
            $this->client->loopForever($this->loop_timeout);
        }catch (Exception $exception){
            $this->logger->log(MqttLogInterface::ERROR,'Connect error:'.$exception->getMessage());
            $this->reconnect();
        }
    }
}