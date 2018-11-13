# jesusslim/mqttclient

PHP mqtt client

## 使用

[English](README.md)
[Chinese]

### 安装

Install:

	composer require jesusslim/mqttclient

如果使用本库的dev-master版本,需要添加composer配置

	"minimum-stability": "dev"
	
### 依赖

	swoole 2.0.8+
    mosquitto.so扩展

### 例子

本库提供两种mqtt客户端实现。第一种基于swoole(已不推荐 参看下文swoole例子)，第二种基于mosquitto(参看下文mosquitto例子)。

两种实现的区别:
* 依赖不同.
* swoole扩展存在分包和crash的bug(在当前最新的4.2.7版本仍未解决)。因此使用时需手动处理这些异常。例如监听进程存活情况,在crash或者收到不完整包时重新启动进程。
* swoole实现的客户端可在一个客户端内同时收发消息，mosquitto实现为阻塞，收发消息需要开启两个进程。
* 一些swoole 和 mosquitto的语法差异

#### mosquitto例子

define your logger:

    class Logger implements \mqttclient\src\swoole\MqttLogInterface {

		public function log($type,$content,$params = []){
		        echo "$type : $content \r\n";
		 }
	}

use Mqttclient (用于收消息)

    $host = '127.0.0.1';
	$port = 1883;
    $r = new \mqttclient\src\mosquitto\MqttClient($host,$port,10017);
    $r->setAuth('username','password');
    $r->setKeepAlive(60);
    $r->setLogger(new Logger());
    $r->setMaxReconnectTimesWhenError(360*12);
    //reconnect interval
    $r->setReconnectInterval(10);
    //subscribe topics,callback's params can be any data we mapped into the container(IOC)
    $r->setTopics(
    [
        new \mqttclient\src\subscribe\Topic('test/slim',function($msg){
            echo "I receive:".$msg."\r\n";}),
        new \mqttclient\src\subscribe\Topic('test/slim3',function(\mqttclient\src\swoole\MqttClient $client,$msg){
            echo "I receive:".$msg." for slim3 \r\n";
            echo $client->getClientId();
        })
    ]
    );
    //set trigger
    $r->on(\mqttclient\src\consts\ClientTriggers::SOCKET_CONNECT,function(){
        //do something
    });
    $r->start();
    
Sender (用于发消息)

    $host = '127.0.0.1';
    $port = 1883;
    $r = new \mqttclient\src\mosquitto\MqttSender($host,$port,10017);
    $r->setAuth('username','password');
    $r->setKeepAlive(60);
    $r->setLogger(new Logger());
    $r->setMaxReconnectTimesWhenError(360*12);
    //reconnect interval
    $r->setReconnectInterval(10);
    $r->setQueue(new Queue());
    $r->start();

需要实现一个消息队列Queue类 实现mqttclient\src\mosquitto\MqttSendingQueue接口，便于在循环中获取需要被发送的消息内容。

#### swoole例子(不推荐)

定义logger 用于日志输出:(实现mqttclient\src\swoole\MqttLogInterface接口 推荐使用最简单的echo或者写文件/redis实现)

    class Logger implements \mqttclient\src\swoole\MqttLogInterface {

		public function log($type,$content,$params = []){
		        echo "$type : $content \r\n";
		 }
	}

定义tmp store 用于暂存程序运行过程中的临时数据(推荐Redis或最简单的内存实现)

	class Store implements \mqttclient\src\swoole\TmpStorageInterface{

    	private $data = [];

	    public function set($message_type, $key, $sub_key, $data, $expire = 3600)
	    {
	        $this->data[$message_type][$key][$sub_key] = $data;
	    }
	
	    public function get($message_type, $key, $sub_key)
	    {
	        return $this->data[$message_type][$key][$sub_key];
	    }
	
	    public function delete($message_type, $key, $sub_key)
	    {
	        if (!isset($this->data[$message_type][$key][$sub_key])){
	            echo "storage not found:$message_type $key $sub_key";
	        }
	        unset($this->data[$message_type][$key][$sub_key]);
	    }

	}

MqttClient示例

	$host = '127.0.0.1';
	$port = 1883;

	$r = new \mqttclient\src\swoole\MqttClient($host,$port,10017);
	$r->setAuth('username','password');
	$r->setKeepAlive(60);
	$r->setLogger(new Logger());
	$r->setStore(new Store());
	//dns lookup
	$r->setDnsLookup(true);
	//缓冲区大小
	$r->setSocketBufferSize(1024*1024*5);
	//最大错误重连次数
	$r->setMaxReconnectTimesWhenError(360*12);
	//尝试重连间隔
    $r->setReconnectInterval(10000);
    //订阅topics 回调以依赖注入方式实现 入参可以是任何在mqttclient容器中注入过的变量 如预定义的msg,\mqttclient\src\swoole\MqttClient $client等等
	$r->setTopics(
    [
        new \mqttclient\src\subscribe\Topic('test/slim',function($msg){
            echo "I receive:".$msg."\r\n";}),
        new \mqttclient\src\subscribe\Topic('test/slim3',function(\mqttclient\src\swoole\MqttClient $client,$msg){
            echo "I receive:".$msg." for slim3 \r\n";
            echo $client->getClientId();
        })
    ]
	);
	
	//set trigger
	$r->on(\mqttclient\src\consts\ClientTriggers::RECEIVE_SUBACK,function(\mqttclient\src\swoole\MqttClient $client){
    	$client->publish('slim/echo','GGXX',\mqttclient\src\consts\Qos::ONE_TIME);
    });
	
	$r->connect();
	$r->publish('test/slim','test qos',2);
	
### Extends

也可以派生自己的Client继承MqttClient 并重写容器函数以注入自定义的变量 如配置、其他数据连接等

Example:

	class Client extends MqttClient
	{
	    private $mysql_handler;
	    private $mongo_handler;
	
	    public function __construct($host,$port,$client_id,$mysql_conf,$mongo_conf)
	    {
	        $this->mysql_handler = new Mysqli($mysql_conf);
	        $this->mongo_handler = new \MongoClient('mongodb://'.$mongo_conf['username'].':'.$mongo_conf['password'].'@'.$mongo_conf['host'].':'.$mongo_conf['port'].'/'.$mongo_conf['db']);
	        parent::__construct($host,$port,$client_id);
	    }
	
		 /**
	     * override the produceContainer function and map your own class/data/closure to the injector,and they can be used in every publish receive handler
	     * for exp: $client->setTopics([new Topic('test/own',function($mongo,$msg){ $result = $mongo->selectCollection('log_platform','test')->find(['sid' => ['$gte' => intval($msg)]]); })]);
	     * @return Injector
	     */
	    protected function produceContainer()
	    {
	        $container = new Injector();
	        $container->mapData(MqttClient::class,$this);
	        $container->mapData(Client::class,$this);
	        $container->mapData('mysqli',$this->mysql_handler);
	        $container->mapData('mongo',$this->mongo_handler);
	        return $container;
	    }
	
	}

### 推荐用法

推荐本项目MqttClient主要作为数据订阅、分发、发送等用途，尽量避免在主进程中进行过多IO操作（例如读写库、调用httpAPi等）而造成主进程阻塞。
IO操作，如入库，http api调用等可以通过子进程swoole_process实现。

例如主进程收到订阅消息，处理后丢入redis队列。子进程循环阻塞读取redis队列进行消费，做入库操作。

如子进程需要发送消息则通过管道通信写入pipeline，主进程通过事件回调读取数据进行发送。

同时主进程需要对子进程进行监控，以便出问题时及时重启，以及释放资源。

需要注意的是swoole_process的创建必须在client连接之前，否则会引发复杂的io问题，在高版本swoole中已抛错。

具体实现本项目中不做展开。