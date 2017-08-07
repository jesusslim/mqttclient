<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/2
 * Time: 下午11:38
 */

namespace mqttclient\src\swoole;


interface MqttLogInterface
{

    const ERROR = 'ERROR';
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';

    public function log($type,$content,$params = []);

}