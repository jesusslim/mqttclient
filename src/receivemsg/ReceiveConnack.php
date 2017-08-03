<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/2
 * Time: 下午10:24
 */

namespace mqttclient\src\receivemsg;

/**
 * 接收Connack消息处理
 * Class ReceiveConnack
 * @package mqttclient\src\receivemsg
 */
class ReceiveConnack
{

    private $success;
    private $err_code;
    private $err_msg;

    public function __construct($bytes_whole)
    {
        $this->err_code = $tag = ord($bytes_whole{3});
        $this->success = $tag == 0;
        $this->err_msg = $this->success ? null : "connack err {$tag}";
    }

    public function isSuccess(){
        return $this->success == true;
    }

    public function getError(){
        return $this->err_msg;
    }
}