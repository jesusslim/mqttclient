<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/2
 * Time: 下午1:36
 */

namespace mqttclient\src;

use Inject\Injector;
use mqttclient\src\consts\MessageType;
use mqttclient\src\consts\Options;
use mqttclient\src\consts\Qos;
use mqttclient\src\message\Connect;
use mqttclient\src\message\Disconnect;
use mqttclient\src\message\Ping;
use mqttclient\src\message\Publish;
use mqttclient\src\message\Subscribe;
use mqttclient\src\message\Util;
use mqttclient\src\message\Will;
use mqttclient\src\receivemsg\ReceiveConnack;
use mqttclient\src\receivemsg\ReceivePublish;

class Client
{

    private $socket;
    private $keep_alive;
    private $host;
    private $port;
    private $client_id;

    //auth 加密 [user_name => '',password => '']
    private $auth;

    //遗嘱
    private $will;

    //subscribe订阅的topic
    private $topics;

    private $ext_opts;

    private $last_ping_receive_time;
    private $last_ping_time;

    private $msg_id;

    //依赖注入容器 用于处理handler闭包
    private $container;

    //终止标志
    private $running;

    /* @var MqttLogInterface $logger */
    private $logger;

    public function __construct($host,$port,$client_id)
    {
        $this->host = $host;
        $this->port = $port;
        $this->client_id = $client_id;
        $this->msg_id = 1;
        $this->topics = [];
        $this->container = new Injector();
        $this->container->mapData(Client::class,$this);
        $this->ext_opts = [
            Options::FSOCK_TIMEOUT => 60,
            Options::STREAM_TIMEOUT => 5,

            Options::PING_INTERVAL_SECONDS => 1,

            Options::PROCESS_INTERVAL_MICRO_SECONDS => 100000,

            Options::RECONNECT_TIMES => 0,
            Options::RECONNECT_TIME_INTERVAL => 5,
        ];
        $this->running = true;
    }

    /**
     * @param int $keep_alive
     * @return $this
     */
    public function setKeepAlive($keep_alive)
    {
        $this->keep_alive = $keep_alive;
        return $this;
    }

    /**
     * @param $user_name
     * @param $password
     * @return $this
     */
    public function setAuth($user_name,$password){
        $this->auth = compact('user_name','password');
        return $this;
    }

    /**
     * @param $array
     * @return $this
     */
    public function setOptions($array){
        foreach ($array as $k => $v){
            if (isset($this->ext_opts[$k])) $this->ext_opts[$k] = $v;
        }
        return $this;
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
     * @return Injector
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param Injector $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
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
    public function setLogger(MqttLogInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * connect
     * @param bool $clean
     * @param Will|null $will
     * @return bool
     */
    public function connect($clean = true,Will $will = null){

        if($will) $this->will = $will;
        $this->socket = fsockopen($this->host, $this->port, $err_no, $err_msg, $this->ext_opts[Options::FSOCK_TIMEOUT]);
        if (! $this->socket ) {
            $this->logger->log(MqttLogInterface::ERROR,"fsockopen fail.$err_no:$err_msg");
            return false;
        }

        stream_set_timeout($this->socket, $this->ext_opts[Options::STREAM_TIMEOUT]);
        stream_set_blocking($this->socket, 0);

        $connect = new Connect($this->client_id,$this->keep_alive,$clean,$this->will,$this->auth);

        fwrite($this->socket,  $connect->getBytes());

        $string = $this->read(4);

        //  固定报头2 + 可变报头2
        //  固定报头第一位为message类型
        //  可变报头第二位 CONNACK结果
        $msg_type = Util::getMessageType($string);
        if ($msg_type != MessageType::CONNACK){
            $this->logger->log(MqttLogInterface::ERROR,"no connack,receive type:$msg_type");
            return false;
        }

        $connack = new ReceiveConnack($string);
        if (!$connack->isSuccess()){
            $this->logger->log(MqttLogInterface::ERROR,$connack->getError());
            return false;
        }

        $this->last_ping_receive_time = time();

        return true;
    }

    /**
     * @return bool
     */
    public function reconnect(){
        $result = false;
        $rec_num = 0;
        $max_times = $this->ext_opts[Options::RECONNECT_TIMES];
        $rec_interval = $this->ext_opts[Options::RECONNECT_TIME_INTERVAL];
        while( $max_times == 0 || $rec_num < $max_times ){
            $r = $this->connect(false);
            if ($r == true){
                $result = true;
                break;
            }
            $rec_num ++;
            sleep($rec_interval);
        }
        return $result;
    }

    /**
     * ping
     */
    public function ping(){
        $ping = new Ping();
        fwrite($this->socket, $ping->getBytes() , 2);
    }

    /**
     * disconnect
     */
    public function disconnect(){
        $disconnect = new Disconnect();
        fwrite($this->socket, $disconnect->getBytes(), 2);
    }

    /**
     * close
     */
    public function close(){
        $this->disconnect();
        fclose($this->socket);
    }

    /**
     * call to stop the loop
     */
    public function callStop(){
        $this->running = false;
    }

    /**
     *
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * @param $length
     * @param bool $nb
     * @return string
     */
    public function read($length,$nb = false){
        if ($nb) return fread($this->socket, $length);
        $string = '';
        $left = $length;
        while (!feof($this->socket) && $left > 0) {
            $fread = fread($this->socket, $left);
            $string .= $fread;
            $left = $length - strlen($string);
        }
        return $string;
    }

    /**
     * publish
     * @param Publish $msg
     */
    public function publish(Publish $msg){
        $bytes = $msg->getBytes($this->msg_id++);
        fwrite($this->socket, $bytes, strlen($bytes));
    }

    /**
     * 订阅
     * @param int $qos
     */
    public function subscribe($qos = Qos::MOST_ONE_TIME){
        if (count($this->topics) == 0) return;
        $sub = new Subscribe($this->topics,$this->msg_id++,$qos);
        $bytes = $sub->getBytes();
        fwrite($this->socket,$bytes,strlen($bytes));
        $string = $this->read(2);
        $bytes = ord(substr($string,1,1));
        $this->read($bytes);
    }

    /**
     * 处理接收到的Publish
     * @param $str
     */
    public function handleReceive($str){
        $pub = new ReceivePublish($str);
        $topic_name = $pub->getTopicName();
        foreach($this->topics as $key => $topic){
            if( preg_match("/^".str_replace("#",".*",
                    str_replace("+","[^\/]*",
                        str_replace("/","\/",
                            str_replace("$",'\$',
                                $key))))."$/",$topic_name) ){
                /* @var \mqttclient\src\subscribe\Topic $topic */
                $this->container->call($topic->getHandler(),['msg' => $pub->getContent()]);
            }
        }
    }

    /**
     * @return bool
     */
    public function process(){
        if (!$this->running) return false;
        if(feof($this->socket)){
            fclose($this->socket);
            $r = $this->reconnect();
            if ($r !== true){
                $this->logger->log(MqttLogInterface::ERROR,"reconnect fail");
            }else{
                $this->subscribe();
            }
        }
        $byte = $this->read(1,true);
        if(!strlen($byte)){
            usleep($this->ext_opts[Options::PROCESS_INTERVAL_MICRO_SECONDS]);
        }else{
            $cmd = Util::getMessageType($byte);
            $this->logger->log(MqttLogInterface::DEBUG,"Received: $cmd");

            //长度
            $multi = 1;
            $length = 0;
            do{
                $digit = ord($this->read(1));
                $length += ($digit & 127) * $multi;
                $multi *= 128;
            }while (($digit & 128) != 0);
            $this->logger->log(MqttLogInterface::DEBUG,"Received length: $length");

            if($length) {
                $str = $this->read($length,true);
            }

            if($cmd){
                switch($cmd){
                    case MessageType::PINGRESP:
                        $this->logger->log(MqttLogInterface::DEBUG,"Receive PINGRESP.");
                        break;
                    case MessageType::PUBLISH:
                        $this->handleReceive($str);
                        break;
                }

                $this->last_ping_receive_time = time();
            }
        }

        if($this->last_ping_receive_time < (time() - $this->keep_alive) && $this->last_ping_time < (time() - $this->ext_opts[Options::PING_INTERVAL_SECONDS])){
            $this->logger->log(MqttLogInterface::DEBUG,"ping");
            $this->last_ping_time = time();
            $this->ping();
        }

        if($this->last_ping_receive_time < (time() - ($this->keep_alive * 2))){
            $this->logger->log(MqttLogInterface::DEBUG,"reconnect");
            fclose($this->socket);
            $r = $this->reconnect();
            if ($r !== true){
                $this->logger->log(MqttLogInterface::ERROR,"reconnect fail");
            }else{
                $this->subscribe();
            }
        }

        return true;
    }
}