<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/3
 * Time: 上午10:14
 */

namespace mqttclient\src\message;


class Util
{

    /**
     * @param $str
     * @return string
     */
    public static function writeToBuffer($str){
        $len = strlen($str);
        //最高有效字节
        $msb = $len >> 8;
        //最低有效字节
        $lsb = $len % 256;
        $result = chr($msb);
        $result .= chr($lsb);
        $result .= $str;
        return $result;
    }

    /**
     * @param $len
     * @return string
     */
    public static function writeMsgLength($len){
        $string = "";
        do{
            $digit = $len % 128;
            $len = $len >> 7;
            if ( $len > 0 ) $digit = ($digit | 0x80);
            $string .= chr($digit);
        }while ( $len > 0 );
        return $string;
    }

    /**
     * 获取消息类型标识
     * @param $bytes
     * @return int
     */
    public static function getMessageType($bytes){
        $first_tag = ord($bytes{0})>>4;
        return $first_tag;
    }
}