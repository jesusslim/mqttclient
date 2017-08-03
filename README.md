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

#### Subscribe

    class Logger implements \mqttclient\src\MqttLogInterface {
        public function log($type,$content,$params = []){
            echo "$type : $content \r\n";
        }
    }
    
    $ip = 'your mqtt server ip';
    $port = 1883;
    $client = new \mqttclient\src\Client($ip,$port,10018);
    $client->setLogger(new Logger());
    $client->setKeepAlive(30)->setAuth('username','password');
    
    // connect
    $r = $client->connect(true,new \mqttclient\src\message\Will('test/slim',"It's a will of sub."));
    
    $client->setTopics(
        [
        
            // topic parten
            new \mqttclient\src\subscribe\Topic('+/slim',function($msg){echo '+/slim , I received:'.$msg."\r\n";}),
        
            // topic name
            new \mqttclient\src\subscribe\Topic('test/slim',function($msg){echo 'test/slim , I received:'.$msg."\r\n";}),
        
            // close loop when received a publish message
            new \mqttclient\src\subscribe\Topic('test/close',function (\mqttclient\src\Client $client,$msg){
                $client->callStop();
            }),
        
            // trigger publish message
            new \mqttclient\src\subscribe\Topic('test/send',function (\mqttclient\src\Client $client){
                $client->publish(new \mqttclient\src\message\Publish('test/slim','Send from self.'));
            })
        ]
    )->subscribe();
    while ($client->process());
    $client->close();
    
#### Publish

    class Logger implements \mqttclient\src\MqttLogInterface {
        public function log($type,$content,$params = []){
            echo "$type : $content \r\n";
        }
    }
    $ip = 'your mqtt server ip';
    $port = 1883;
    $client = new \mqttclient\src\Client($ip,$port,10017);
    $client->setLogger(new Logger());
    $client->setKeepAlive(30)->setAuth('username','password');
    $r = $client->connect(true,new \mqttclient\src\message\Will('test/slim',"It's a will."));    
    
    $client->publish(new \mqttclient\src\message\Publish('test/send',"You won't let me."));
    

    