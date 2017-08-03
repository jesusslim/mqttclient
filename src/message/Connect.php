<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/3
 * Time: 上午10:09
 */

namespace mqttclient\src\message;

/**
 * Class Connect
 * @package mqttclient\src\message
 */
class Connect extends BaseMessage
{

    /**
     * Connect constructor.
     * @param $client_id
     * @param $keep_alive
     * @param $clean
     * @param Will $will
     * @param $auth
     */
    public function __construct($client_id,$keep_alive,$clean,Will $will,$auth)
    {
        // 可变报头

        // 前七位
        // 0 100 M Q T T level(100)
        $buffer = "";
        $buffer .= chr(0x00);
        $buffer .= chr(0x04);
        $buffer .= chr(0x4d);
        $buffer .= chr(0x51);
        $buffer .= chr(0x54);
        $buffer .= chr(0x54);
        $buffer .= chr(0x04);

        //第八位 连接标志位
        // 7            6           5               4 3         2           1               0
        // user name    password    will retain     will qos    will flag   clean session   reserved
        $flag = 0;
        if ($clean) $flag += 2;

        if ($will){
            $flag += 4;
            $flag += ($will->getQos() << 3);
            if($will->getRetain()) $flag += 32;
        }

        if ($auth['user_name']) $flag += 128;
        if ($auth['password']) $flag += 64;

        $buffer .= chr($flag);

        //第九位 第十位 keep alive
        //Keep alive
        $buffer .= chr($keep_alive >> 8);
        $buffer .= chr($keep_alive & 0xff);

        $buffer .= Util::writeToBuffer($client_id);

        if ($will){
            $buffer .= Util::writeToBuffer($will->getTopic());
            $buffer .= Util::writeToBuffer($will->getContent());
        }

        if ($auth['user_name']) $buffer .= Util::writeToBuffer($auth['user_name']);
        if ($auth['password']) $buffer .= Util::writeToBuffer($auth['password']);


        // 固定报头

        $head = "";
        $head .= chr(0x10);
        $head .= chr(strlen($buffer));

        $this->bytes = $head.$buffer;
    }
}