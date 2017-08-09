# jesusslim/mqttclient

PHP mqtt client

## usage

### Install

Install:

	composer require jesusslim/mqttclient

If your composer not allowed dev-master,add this config

	"minimum-stability": "dev"
	
into your composer.json.

### Example

define your logger:

    class Logger implements \mqttclient\src\swoole\MqttLogInterface {

		public function log($type,$content,$params = []){
		        echo "$type : $content \r\n";
		 }
	}

define your tmp store (use Redis/Memory/...)

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

use MqttClient

	$host = '127.0.0.1';
	$port = 1883;

	$r = new \mqttclient\src\swoole\MqttClient($host,$port,10017);
	$r->setAuth('username','password');
	$r->setKeepAlive(60);
	$r->setLogger(new Logger());
	$r->setStore(new Store());
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
	
